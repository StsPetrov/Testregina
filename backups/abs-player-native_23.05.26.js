// abs-player-native.js — плеер на HTML5 Audio (без Howler)
var tracks = [];
var mainAudio = null;
var mainCurrentTrackIndex = 0;
var mainIsPlaying = false;
var mainSpeed = 1;
var mainVolume = 0.8;
var uiTimer = null;
var saveTimer = null;

function fmt(s) {
    if (isNaN(s) || s === undefined) return '0:00';
    s = Math.floor(s);
    var m = Math.floor(s / 60), sec = s % 60;
    return m + ':' + (sec < 10 ? '0' : '') + sec;
}

function getTrackUrl(trackIndex) {
    if (!tracks[trackIndex]) return '';
    var track = tracks[trackIndex];
    if (track.book_folder && track.file_name) {
        return absPlayerData.minioUrl + '/' + track.book_folder + '/' + track.file_name;
    }
    return '';
}

function preloadTrack(index) {
    if (!tracks[index]) return;
    var audio = new Audio();
    audio.crossOrigin = 'anonymous';
    audio.src = getTrackUrl(index);
    audio.preload = 'auto';
    audio.load();
}

function preloadNextTracks() {
    preloadTrack(mainCurrentTrackIndex + 1);
    preloadTrack(mainCurrentTrackIndex + 2);
}

function updateUI() {
    if (!mainAudio) return;
    var elapsed = mainAudio.currentTime || 0;
    var duration = mainAudio.duration || 0;
    var ct = document.getElementById('current-time');
    var dt = document.getElementById('duration-time');
    var sl = document.getElementById('abs-seek-slider');
    if (ct) ct.innerText = fmt(elapsed);
    if (dt && !isNaN(duration) && duration > 0) dt.innerText = fmt(duration);
    if (sl && duration > 0) { sl.max = Math.floor(duration); sl.value = Math.floor(elapsed); }
    var tt = document.getElementById('current-track-title');
    if (tt && tracks[mainCurrentTrackIndex]) {
        var n = tracks[mainCurrentTrackIndex].name || ('Трек ' + (mainCurrentTrackIndex + 1));
        tt.innerText = n.replace(/\.[^/.]+$/, '');
    }
    document.querySelectorAll('#abs-track-list li').forEach(function(li, i) {
        li.classList.toggle('active-track', i === mainCurrentTrackIndex);
    });
}

function startUITimer() { if (uiTimer) clearInterval(uiTimer); uiTimer = setInterval(updateUI, 300); }
function stopUITimer() { if (uiTimer) { clearInterval(uiTimer); uiTimer = null; } }

function startSaveTimer() {
    if (saveTimer) clearInterval(saveTimer);
    saveTimer = setInterval(function() {
        if (mainAudio && mainIsPlaying && !mainAudio.paused) {
            var t = Math.floor(mainAudio.currentTime || 0);
            try { localStorage.setItem('abs_pos', JSON.stringify({ b: absPlayerData.postId, t: mainCurrentTrackIndex, s: t, ts: Date.now() })); } catch(e) {}
            jQuery.post(absPlayerData.ajaxUrl, { action: 'save_abs_progress', book_id: absPlayerData.postId, track_index: mainCurrentTrackIndex, progress_seconds: t });
        }
    }, 5000);
}
function stopSaveTimer() { if (saveTimer) { clearInterval(saveTimer); saveTimer = null; } }

function updateBtns() {
    var s = document.getElementById('abs-play-pause-small');
    var b = document.getElementById('abs-play-pause-big');
    if (s) s.innerHTML = mainIsPlaying ? '⏸' : '▶';
    if (b) b.innerHTML = mainIsPlaying ? '⏸' : '▶';
}

function scrollToActiveTrack() {
    var list = document.getElementById('abs-track-list');
    if (!list) return;
    var items = list.querySelectorAll('li');
    if (items[mainCurrentTrackIndex]) {
        var item = items[mainCurrentTrackIndex];
        list.scrollTop = item.offsetTop - list.offsetTop - 100;
    }
}

function playCurrentTrack() {
    if (!tracks.length) return;
    if (mainAudio) {
        mainAudio.pause();
        mainAudio.removeAttribute('src');
        mainAudio.load();
        mainAudio = null;
    }
    stopUITimer();
    stopSaveTimer();
    var url = getTrackUrl(mainCurrentTrackIndex);
    mainAudio = new Audio();
    mainAudio.crossOrigin = 'anonymous';
    mainAudio.src = url;
    mainAudio.playbackRate = mainSpeed;
    mainAudio.volume = mainVolume;
    mainAudio.onloadedmetadata = function() {
        updateUI();
        updateBtns();
        scrollToActiveTrack();
        preloadNextTracks();
        mainAudio.play().then(function() {
            mainIsPlaying = true;
            updateBtns();
            startUITimer();
            startSaveTimer();
        }).catch(function(e) {
            console.error('Play error:', e);
            mainIsPlaying = false;
            updateBtns();
        });
    };
    mainAudio.onended = function() {
        stopUITimer();
        stopSaveTimer();
        if (mainCurrentTrackIndex + 1 < tracks.length) {
            mainCurrentTrackIndex++;
            playCurrentTrack();
        } else {
            mainIsPlaying = false;
            updateBtns();
        }
    };
    mainAudio.onerror = function(e) {
        console.error('Audio error:', e);
        mainIsPlaying = false;
        updateBtns();
    };
}

function togglePlay() {
    if (mainAudio && !mainAudio.paused) {
        mainAudio.pause();
        mainIsPlaying = false;
        stopUITimer();
        stopSaveTimer();
        updateBtns();
    } else if (mainAudio && mainAudio.src) {
        mainAudio.play().then(function() {
            mainIsPlaying = true;
            updateBtns();
            startUITimer();
            startSaveTimer();
        }).catch(function(e) {
            console.error('Play error:', e);
        });
    } else if (tracks.length) {
        mainIsPlaying = true;
        playCurrentTrack();
    }
}

function prevTrack() {
    if (mainCurrentTrackIndex > 0) {
        mainCurrentTrackIndex--;
        if (mainIsPlaying) playCurrentTrack();
        else scrollToActiveTrack();
    }
}

function nextTrack() {
    if (mainCurrentTrackIndex + 1 < tracks.length) {
        mainCurrentTrackIndex++;
        if (mainIsPlaying) playCurrentTrack();
        else scrollToActiveTrack();
    }
}

function seekTo(s) {
    if (mainAudio) {
        mainAudio.currentTime = s;
        updateUI();
    }
}

function setVolume(v) {
    mainVolume = v;
    if (mainAudio) mainAudio.volume = v;
}

function setSpeed(s) {
    mainSpeed = s;
    if (mainAudio) mainAudio.playbackRate = s;
}

function restoreProgress() {
    jQuery.get(absPlayerData.ajaxUrl, { action: 'get_abs_progress', book_id: absPlayerData.postId }, function(r2) {
        if (r2.success && r2.data && tracks.length > 0 && tracks[r2.data.track_index]) {
            mainCurrentTrackIndex = r2.data.track_index;
            playCurrentTrack();
            var wait = setInterval(function() {
                if (mainAudio && mainAudio.duration > 0) {
                    clearInterval(wait);
                    mainAudio.currentTime = r2.data.progress_seconds || 0;
                    updateUI();
                    mainAudio.pause();
                    mainIsPlaying = false;
                    updateBtns();
                }
            }, 200);
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    if ('mediaSession' in navigator) {
    navigator.mediaSession.setActionHandler('play', function() {
        if (mainAudio && mainAudio.paused) {
            mainAudio.play().then(function() {
                mainIsPlaying = true;
                updateBtns();
                startUITimer();
                startSaveTimer();
            });
        }
    });
    navigator.mediaSession.setActionHandler('pause', function() {
        if (mainAudio && !mainAudio.paused) {
            mainAudio.pause();
            mainIsPlaying = false;
            stopUITimer();
            stopSaveTimer();
            updateBtns();
        }
    });
    navigator.mediaSession.setActionHandler('previoustrack', function() { prevTrack(); });
    navigator.mediaSession.setActionHandler('nexttrack', function() { nextTrack(); });
    navigator.mediaSession.setActionHandler('seekforward', function() {
        if (mainAudio) { mainAudio.currentTime = Math.min(mainAudio.duration, mainAudio.currentTime + 10); updateUI(); }
    });
    navigator.mediaSession.setActionHandler('seekbackward', function() {
        if (mainAudio) { mainAudio.currentTime = Math.max(0, mainAudio.currentTime - 10); updateUI(); }
    });
}
            if (mainAudio) { mainAudio.currentTime = Math.min(mainAudio.duration, mainAudio.currentTime + 10); updateUI(); }
        });
        navigator.mediaSession.setActionHandler('seekbackward', function() {
            if (mainAudio) { mainAudio.currentTime = Math.max(0, mainAudio.currentTime - 10); updateUI(); }
        });
    }

    if (!absPlayerData || !absPlayerData.itemId) return;
    var sp = document.getElementById('abs-play-pause-small');
    var bp = document.getElementById('abs-play-pause-big');
    if (sp) sp.onclick = togglePlay;
    if (bp) bp.onclick = togglePlay;
    document.getElementById('abs-prev') && (document.getElementById('abs-prev').onclick = prevTrack);
    document.getElementById('abs-next') && (document.getElementById('abs-next').onclick = nextTrack);
    var sl = document.getElementById('abs-seek-slider');
    if (sl) sl.oninput = function() { seekTo(parseInt(this.value)); };
    var vb = document.getElementById('abs-volume-btn'), vs = document.getElementById('abs-volume-slider');
    if (vb && vs) {
        vb.onclick = function(e) { e.stopPropagation(); vs.style.display = vs.style.display === 'none' ? 'block' : 'none'; };
        vs.oninput = function() { setVolume(this.value / 100); };
        vs.value = mainVolume * 100;
        document.addEventListener('click', function(e) { if (!vb.contains(e.target) && !vs.contains(e.target)) vs.style.display = 'none'; });
    }
    var sbb = document.getElementById('abs-speed-btn'), sbm = document.getElementById('abs-speed-menu');
    if (sbb && sbm) {
        sbb.onclick = function(e) { e.stopPropagation(); sbm.style.display = sbm.style.display === 'none' ? 'block' : 'none'; };
        document.querySelectorAll('#abs-speed-menu button').forEach(function(b) { b.onclick = function() { setSpeed(parseFloat(this.dataset.speed)); sbb.innerText = mainSpeed + 'x'; sbm.style.display = 'none'; }; });
    }

    jQuery.get(absPlayerData.ajaxUrl, { action: 'get_abs_book_data', book_id: absPlayerData.itemId }, function(r) {
        if (!r.success) return;
        var data = r.data;
        tracks = data.tracks || [];
        if (data.total_duration_formatted) absPlayerData.totalDurationFormatted = data.total_duration_formatted;
        var m = data.book_data.media?.metadata || {};
        if ('mediaSession' in navigator) {
            navigator.mediaSession.metadata = new MediaMetadata({
                title: m.title || 'Аудиокнига',
                artist: m.authorName || m.author || 'Автор',
            });
        }
        var bt = document.getElementById('book-title'); if (bt) bt.innerText = m.title || 'Аудиокнига';
        var ba = document.getElementById('book-author'); if (ba) ba.innerHTML = m.authorName || m.author || 'Автор не указан';
        var bd = document.getElementById('book-description'); if (bd) bd.innerText = (m.description || '').replace(/<[^>]*>/g, '');
        var tg = document.getElementById('book-tags'); if (tg && m.genres) { tg.innerHTML = ''; m.genres.forEach(function(g) { var s = document.createElement('span'); s.className='book-tag'; s.innerText=g; tg.appendChild(s); }); }
        var md = document.getElementById('book-meta'); if (md && absPlayerData.totalDurationFormatted) md.innerHTML = '<div class="book-meta-item">Длительность: ' + absPlayerData.totalDurationFormatted + '</div>';
        var cv = document.getElementById('book-cover');
        if (cv) { var img = new Image(); img.onload = function() { cv.innerHTML = '<img src="' + absPlayerData.ajaxUrl + '?action=get_abs_cover&book_id=' + absPlayerData.itemId + '">'; }; img.onerror = function() { cv.innerHTML = '<div class="no-cover">📖</div>'; }; img.src = absPlayerData.ajaxUrl + '?action=get_abs_cover&book_id=' + absPlayerData.itemId; }
        var list = document.getElementById('abs-track-list');
        if (list) { list.innerHTML = ''; tracks.forEach(function(t, i) { var li = document.createElement('li'); var n = (t.name || 'Трек ' + (i+1)).replace(/\.[^/.]+$/, ''); li.innerHTML = '<span class="track-num">' + (i+1).toString().padStart(2,'0') + '</span><span class="track-title">' + n.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;') + '</span><span class="track-duration" id="track-duration-' + i + '">--:--</span>'; li.onclick = function() { mainCurrentTrackIndex = i; playCurrentTrack(); }; list.appendChild(li); }); }
        jQuery.get(absPlayerData.ajaxUrl, { action: 'get_abs_durations', book_id: absPlayerData.itemId }, function(r2) { if (r2.success && r2.data?.tracks) { r2.data.tracks.forEach(function(t) { var s = document.getElementById('track-duration-' + t.track_index); if (s && t.duration_formatted !== '--:--') { s.innerText = t.duration_formatted; s.classList.add('loaded'); } }); } });
        jQuery.get(absPlayerData.ajaxUrl, { action: 'get_abs_rating', book_id: absPlayerData.postId }, function(r2) { if (r2.success && r2.data) { var rv = document.getElementById('rating-value'); var rc = document.getElementById('rating-count'); if (rv) rv.innerText = Number(r2.data.average).toFixed(1); if (rc) rc.innerText = '(' + r2.data.count + ' голосов)'; document.querySelectorAll('.star').forEach(function(s) { var v = parseInt(s.dataset.value); s.classList.toggle('active', v <= Math.round(r2.data.average)); s.innerText = v <= Math.round(r2.data.average) ? '★' : '☆'; }); } });
        jQuery.get(absPlayerData.ajaxUrl, { action: 'get_book_status', book_id: absPlayerData.postId }, function(r2) { if (r2.success && r2.data?.status) { var sel = document.getElementById('book-status-select'); if (sel) sel.value = r2.data.status; } });
        jQuery.get(absPlayerData.ajaxUrl, { action: 'is_favorite', book_id: absPlayerData.postId }, function(r2) { if (r2.success) { var fb = document.getElementById('abs-favorite-btn'); if (fb) { fb.innerHTML = r2.data.favorite ? '❤️' : '♡'; fb.classList.toggle('active', r2.data.favorite); } } });
        document.getElementById('book-status-select')?.addEventListener('change', function() { jQuery.post(absPlayerData.ajaxUrl, { action: 'save_book_status', book_id: absPlayerData.postId, status: this.value }); });
        document.getElementById('abs-favorite-btn')?.addEventListener('click', function() { var a = this.classList.toggle('active'); this.innerHTML = a ? '❤️' : '♡'; jQuery.post(absPlayerData.ajaxUrl, { action: 'toggle_favorite', book_id: absPlayerData.postId }); });
        restoreProgress();
    });
});
window.addEventListener('beforeunload', function() {
    if (mainAudio && mainCurrentTrackIndex !== undefined) {
        var t = Math.floor(mainAudio.currentTime || 0);
        try { localStorage.setItem('abs_pos', JSON.stringify({ b: absPlayerData.postId, t: mainCurrentTrackIndex, s: t })); } catch(e) {}
    }
});