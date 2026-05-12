// abs-player.js

if (typeof Howler !== 'undefined') Howler.pool = 50;

var tracks = [];
var mainSound = null;
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

function updateUI() {
    if (!mainSound) return;
    var c = mainSound.seek() || 0, d = mainSound.duration() || 0;
    var ct = document.getElementById('current-time'), dt = document.getElementById('duration-time'), sl = document.getElementById('abs-seek-slider');
    if (ct) ct.innerText = fmt(c);
    if (dt && d > 0) dt.innerText = fmt(d);
    if (sl && d > 0) { sl.max = Math.floor(d); sl.value = Math.floor(c); }
    var tt = document.getElementById('current-track-title');
    if (tt && tracks[mainCurrentTrackIndex]) { var n = tracks[mainCurrentTrackIndex].name || ('Трек ' + (mainCurrentTrackIndex + 1)); tt.innerText = n.replace(/\.[^/.]+$/, ''); }
    document.querySelectorAll('#abs-track-list li').forEach(function(li, i) { li.classList.toggle('active-track', i === mainCurrentTrackIndex); });
}

function startUITimer() { if (uiTimer) clearInterval(uiTimer); uiTimer = setInterval(updateUI, 300); }
function stopUITimer() { if (uiTimer) { clearInterval(uiTimer); uiTimer = null; } }

function startSaveTimer() {
    if (saveTimer) clearInterval(saveTimer);
    saveTimer = setInterval(function() {
        if (mainSound && mainIsPlaying && mainCurrentTrackIndex !== undefined) {
            var t = Math.floor(mainSound.seek() || 0);
            try { localStorage.setItem('abs_pos', JSON.stringify({ b: absPlayerData.itemId, t: mainCurrentTrackIndex, s: t, ts: Date.now() })); } catch(e) {}
            jQuery.post(absPlayerData.ajaxUrl, { action: 'save_abs_progress', book_id: absPlayerData.itemId, track_index: mainCurrentTrackIndex, progress_seconds: t });
        }
    }, 5000);
}
function stopSaveTimer() { if (saveTimer) { clearInterval(saveTimer); saveTimer = null; } }

function updateBtns() {
    var s = document.getElementById('abs-play-pause-small'), b = document.getElementById('abs-play-pause-big');
    if (s) s.innerHTML = mainIsPlaying ? '⏸' : '▶';
    if (b) b.innerHTML = mainIsPlaying ? '⏸' : '▶';
}

function playCurrentTrack(shouldPlay) {
    if (!tracks.length) return;
    if (mainSound) { mainSound.stop(); mainSound.unload(); mainSound = null; }
    stopUITimer(); stopSaveTimer();
    var track = tracks[mainCurrentTrackIndex];
    var token = absPlayerData.apiKey.replace(/\s/g, '').replace(/[\n\r]/g, '');
    var url = absPlayerData.serverUrl + '/api/items/' + absPlayerData.itemId + '/file/' + track.file_id + '?token=' + encodeURIComponent(token);
    mainSound = new Howl({
        src: [url], html5: true, format: ['mp3', 'm4a', 'opus', 'ogg', 'flac'],
        rate: mainSpeed, volume: mainVolume,
        onload: function() {
            updateUI();
            if (shouldPlay !== false) { mainSound.play(); }
            updateBtns();
        },
        onplay: function() { mainIsPlaying = true; updateBtns(); startUITimer(); startSaveTimer(); },
        onpause: function() { mainIsPlaying = false; updateBtns(); },
        onend: function() {
            stopUITimer(); stopSaveTimer();
            if (mainCurrentTrackIndex + 1 < tracks.length) { mainCurrentTrackIndex++; playCurrentTrack(); }
            else { mainIsPlaying = false; updateBtns(); }
        }
    });
    
    // Preload следующих 2 треков
    var next1 = mainCurrentTrackIndex + 1;
    var next2 = mainCurrentTrackIndex + 2;
    [next1, next2].forEach(function(idx) {
        if (idx < tracks.length) {
            var nextTrack = tracks[idx];
            var nextUrl = absPlayerData.serverUrl + '/api/items/' + absPlayerData.itemId + '/file/' + nextTrack.file_id + '?token=' + encodeURIComponent(token);
            var preload = new Audio();
            preload.src = nextUrl;
            preload.preload = 'auto';
            preload.load();
        }
    });
}

function togglePlay() {
    if (mainSound) {
        if (mainIsPlaying) { mainSound.pause(); } else { mainSound.play(); }
    } else if (tracks.length) {
        mainIsPlaying = true; playCurrentTrack();
    }
}

function prevTrack() { if (mainCurrentTrackIndex > 0) { mainCurrentTrackIndex--; if (mainIsPlaying) playCurrentTrack(); } }
function nextTrack() { if (mainCurrentTrackIndex + 1 < tracks.length) { mainCurrentTrackIndex++; if (mainIsPlaying) playCurrentTrack(); } }

function seekTo(s) { if (mainSound) { mainSound.seek(s); updateUI(); } }
function setVolume(v) { mainVolume = v; if (mainSound) mainSound.volume(v); }
function setSpeed(s) { mainSpeed = s; if (mainSound) mainSound.rate(s); }

function restoreProgress() {
    // 1. Пробуем загрузить с сервера
    jQuery.get(absPlayerData.ajaxUrl, { action: 'get_abs_progress', book_id: absPlayerData.itemId }, function(r2) {
        if (r2.success && r2.data && tracks.length > 0 && tracks[r2.data.track_index]) {
            mainCurrentTrackIndex = r2.data.track_index;
            playCurrentTrack(false);
            waitAndSeek(r2.data.progress_seconds);
            return;
        }
        // 2. Сервер не ответил — пробуем localStorage
        restoreFromLocalStorage();
    }).fail(function() {
        // 3. Ошибка AJAX — пробуем localStorage
        restoreFromLocalStorage();
    });
}

function restoreFromLocalStorage() {
    try {
        var saved = JSON.parse(localStorage.getItem('abs_pos'));
        if (saved && saved.b === absPlayerData.itemId && tracks.length > 0 && tracks[saved.t]) {
            mainCurrentTrackIndex = saved.t;
            playCurrentTrack(false);
            waitAndSeek(saved.s);
            return;
        }
    } catch(e) {}
    // 4. Ничего не найдено
    if (tracks.length > 0) {
        mainCurrentTrackIndex = 0;
        playCurrentTrack(false);
    }
}

function waitAndSeek(seconds) {
    var attempts = 0;
    var wt = setInterval(function() {
        attempts++;
        if (mainSound && mainSound.duration() > 0) {
            clearInterval(wt);
            mainSound.seek(seconds);
            updateUI();
            scrollToActiveTrack();
        } else if (attempts > 50) {
            clearInterval(wt);
        }
    }, 200);
}

function scrollToActiveTrack() {
    setTimeout(function() {
        var active = document.querySelector('#abs-track-list li.active-track');
        if (active) {
            active.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }, 300);
};
// Инициализация
document.addEventListener('DOMContentLoaded', function() {
    if (!absPlayerData || !absPlayerData.itemId) { return; }

    // Кнопки
    var sp = document.getElementById('abs-play-pause-small'), bp = document.getElementById('abs-play-pause-big');
    if (sp) sp.onclick = togglePlay; if (bp) bp.onclick = togglePlay;
    document.getElementById('abs-prev') && (document.getElementById('abs-prev').onclick = prevTrack);
    document.getElementById('abs-next') && (document.getElementById('abs-next').onclick = nextTrack);
    var sl = document.getElementById('abs-seek-slider');
    if (sl) sl.oninput = function() { seekTo(parseInt(this.value)); };

    // Громкость
    var vb = document.getElementById('abs-volume-btn'), vs = document.getElementById('abs-volume-slider');
    if (vb && vs) {
        vb.onclick = function(e) { e.stopPropagation(); vs.style.display = vs.style.display === 'none' ? 'block' : 'none'; };
        vs.oninput = function() { setVolume(this.value / 100); };
        vs.value = mainVolume * 100;
        document.addEventListener('click', function(e) { if (!vb.contains(e.target) && !vs.contains(e.target)) vs.style.display = 'none'; });
    }
    // Скорость
    var sbb = document.getElementById('abs-speed-btn'), sbm = document.getElementById('abs-speed-menu');
    if (sbb && sbm) {
        sbb.onclick = function(e) { e.stopPropagation(); sbm.style.display = sbm.style.display === 'none' ? 'block' : 'none'; };
        document.querySelectorAll('#abs-speed-menu button').forEach(function(b) { b.onclick = function() { setSpeed(parseFloat(this.dataset.speed)); sbb.innerText = mainSpeed + 'x'; sbm.style.display = 'none'; }; });
    }

    // Загрузка данных книги
    jQuery.get(absPlayerData.ajaxUrl, { action: 'get_abs_book_data', book_id: absPlayerData.itemId }, function(r) {
        if (!r.success) { var bt = document.getElementById('book-title'); if (bt) bt.innerText = 'Книга не найдена'; return; }
        var data = r.data;
        tracks = data.tracks || [];
        if (data.total_duration_formatted) absPlayerData.totalDurationFormatted = data.total_duration_formatted;
        // Метаданные
        var m = data.book_data.media?.metadata || {};
        var bt = document.getElementById('book-title'); if (bt) bt.innerText = m.title || 'Аудиокнига';
        document.getElementById('book-author').innerHTML = m.authorName || m.author || 'Автор не указан';
        document.getElementById('book-description').innerText = (m.description || '').replace(/<[^>]*>/g, '');
        var tg = document.getElementById('book-tags'); if (tg && m.genres) { tg.innerHTML = ''; m.genres.forEach(function(g) { var s = document.createElement('span'); s.className='book-tag'; s.innerText=g; tg.appendChild(s); }); }
        var md = document.getElementById('book-meta'); if (md && absPlayerData.totalDurationFormatted) md.innerHTML = '<div class="book-meta-item">Длительность: ' + absPlayerData.totalDurationFormatted + '</div>';
        var cv = document.getElementById('book-cover');
        if (cv) { var img = new Image(); img.onload = function() { cv.innerHTML = '<img src="' + absPlayerData.ajaxUrl + '?action=get_abs_cover&book_id=' + absPlayerData.itemId + '">'; }; img.onerror = function() { cv.innerHTML = '<div class="no-cover">📖</div>'; }; img.src = absPlayerData.ajaxUrl + '?action=get_abs_cover&book_id=' + absPlayerData.itemId; }
        // Треки
        var list = document.getElementById('abs-track-list');
        if (list) { list.innerHTML = ''; tracks.forEach(function(t, i) { var li = document.createElement('li'); var n = (t.name || 'Трек ' + (i+1)).replace(/\.[^/.]+$/, ''); li.innerHTML = '<span class="track-num">' + (i+1).toString().padStart(2,'0') + '</span><span class="track-title">' + n.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;') + '</span><span class="track-duration" id="track-duration-' + i + '">--:--</span>'; li.onclick = function() { mainCurrentTrackIndex = i; playCurrentTrack(); }; list.appendChild(li); }); }
        // Длительности треков
        jQuery.get(absPlayerData.ajaxUrl, { action: 'get_abs_durations', book_id: absPlayerData.itemId }, function(r2) { if (r2.success && r2.data?.tracks) { r2.data.tracks.forEach(function(t) { var s = document.getElementById('track-duration-' + t.track_index); if (s && t.duration_formatted !== '--:--') { s.innerText = t.duration_formatted; s.classList.add('loaded'); } }); } });
        // Рейтинг, статус, избранное
        jQuery.get(absPlayerData.ajaxUrl, { action: 'get_abs_rating', book_id: absPlayerData.itemId }, function(r2) { if (r2.success && r2.data) { var rv = document.getElementById('rating-value'); var rc = document.getElementById('rating-count'); if (rv) rv.innerText = Number(r2.data.average).toFixed(1); if (rc) rc.innerText = '(' + r2.data.count + ' голосов)'; document.querySelectorAll('.star').forEach(function(s) { var v = parseInt(s.dataset.value); s.classList.toggle('active', v <= Math.round(r2.data.average)); s.innerText = v <= Math.round(r2.data.average) ? '★' : '☆'; }); } });
        jQuery.get(absPlayerData.ajaxUrl, { action: 'get_book_status', book_id: absPlayerData.itemId }, function(r2) { if (r2.success && r2.data?.status) { var sel = document.getElementById('book-status-select'); if (sel) sel.value = r2.data.status; } });
        jQuery.get(absPlayerData.ajaxUrl, { action: 'is_favorite', book_id: absPlayerData.itemId }, function(r2) { if (r2.success) { var fb = document.getElementById('abs-favorite-btn'); if (fb) { fb.innerHTML = r2.data.favorite ? '❤️' : '♡'; fb.classList.toggle('active', r2.data.favorite); } } });
        // Статус и избранное — действия
        document.getElementById('book-status-select')?.addEventListener('change', function() { jQuery.post(absPlayerData.ajaxUrl, { action: 'save_book_status', book_id: absPlayerData.itemId, status: this.value }); });
        document.getElementById('abs-favorite-btn')?.addEventListener('click', function() { var a = this.classList.toggle('active'); this.innerHTML = a ? '❤️' : '♡'; jQuery.post(absPlayerData.ajaxUrl, { action: 'toggle_favorite', book_id: absPlayerData.itemId }); });
        // Восстановление прогресса
        restoreProgress();
    });
});

// Сохранение при уходе
window.addEventListener('beforeunload', function() {
    if (mainSound && mainCurrentTrackIndex !== undefined) {
        var t = Math.floor(mainSound.seek() || 0);
        try { localStorage.setItem('abs_pos', JSON.stringify({ b: absPlayerData.itemId, t: mainCurrentTrackIndex, s: t })); } catch(e) {}
    }
});

// Восстановление при возврате в приложение (iOS "Домой")
window.addEventListener('pageshow', function(e) {
    if (e.persisted) {
        if (mainSound) { mainSound.stop(); mainSound.unload(); mainSound = null; }
        stopUITimer();
        stopSaveTimer();
        mainCurrentTrackIndex = 0;
        mainIsPlaying = false;
        restoreProgress();
    }
});

// Шорткоды
document.addEventListener('click', function(e) { var b = e.target.closest('.play-btn'); if (b) { e.preventDefault(); var h = b.getAttribute('href') || b.closest('.book-card')?.querySelector('.book-title-link')?.href; if (h && h !== '#') window.location.href = h; return false; } });

jQuery(document).on('click', '.continue-btn', function(e) { e.preventDefault(); var b = jQuery(this); jQuery.get(absPlayerData.ajaxUrl, { action: 'get_page_by_book_id', book_id: b.data('book-id') }, function(r) { if (r.success?.data?.url) window.location.href = r.data.url + '?start_track=' + b.data('track-index') + '&start_time=' + b.data('current-time'); }); });