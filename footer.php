<?php
/**
 * The template for displaying the footer.
 *
 * Contains the closing of the #content div and all content after
 *
 * @package Audio Podcast
 */
?>

    <footer role="contentinfo">
        <?php if (get_theme_mod('audio_podcast_footer_hide_show', true)){ ?>
            
        <?php }?> 
        <div class="footer">
            <div id="footer-2" class="pt-3 pb-3 text-center">
                <div class="copyright container">
                    <p class="mb-0">
                        <a href="/о-проекте">О проекте</a> |
                        <a href="/об-авторском-праве-dmca">Правообладателям</a> |
                        <a href="/пользовательское-соглашение">Пользовательское соглашение</a> |
                        <a href="/политика-конфиденциальности">Политика конфиденциальности</a>
                    </p>
                    <p class="mb-0" style="margin-top:5px; color:#888;">© 2025 1001ranobe.ru</p>
                </div>
                <div class="clear"></div>
            </div>
        </div>
    </footer>
     <?php if ( get_theme_mod( 'audio_podcast_progress_bar', 0 ) ) : ?>
      <div id="audio_podcast_elemento_progress_bar"></div>
    <?php endif; ?>
        <?php wp_footer(); ?>


        <!-- Yandex.Metrika counter -->
<script type="text/javascript">
    (function(m,e,t,r,i,k,a){
        m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
        m[i].l=1*new Date();
        for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
        k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)
    })(window, document,'script','https://mc.yandex.ru/metrika/tag.js?id=109088439', 'ym');

    ym(109088439, 'init', {ssr:true, webvisor:true, clickmap:true, ecommerce:"dataLayer", referrer: document.referrer, url: location.href, accurateTrackBounce:true, trackLinks:true});
</script>
<noscript><div><img src="https://mc.yandex.ru/watch/109088439" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
<!-- /Yandex.Metrika counter -->
<div id="pwa-install-bar" style="display:none;position:fixed;bottom:0;left:0;right:0;background:#1b2039;padding:12px 20px;text-align:center;z-index:9999;border-top:2px solid #0dcaf0;">
    <span style="color:#fff;margin-right:10px;">📱 Установить приложение</span>
    <button onclick="this.parentElement.style.display='none';" style="background:#0dcaf0;color:#1b2039;border:none;padding:8px 20px;border-radius:20px;font-weight:bold;cursor:pointer;">Установить</button>
</div>
<script>
if (window.matchMedia('(display-mode: standalone)').matches || navigator.standalone) {
    document.getElementById('pwa-install-bar').style.display = 'none';
} else {
    window.addEventListener('beforeinstallprompt', function(e) {
        e.preventDefault();
        window.installPrompt = e;
        document.getElementById('pwa-install-bar').style.display = 'block';
        document.querySelector('#pwa-install-bar button').onclick = function() {
            window.installPrompt.prompt();
            document.getElementById('pwa-install-bar').style.display = 'none';
        };
    });
    if (/iPhone|iPad|iPod/.test(navigator.userAgent)) {
        document.getElementById('pwa-install-bar').style.display = 'block';
        document.querySelector('#pwa-install-bar button').textContent = 'Как?';
        document.querySelector('#pwa-install-bar button').onclick = function() {
            alert('Нажмите «Поделиться» (↙️) в Safari, затем «На экран Домой»');
        };
    }
}
</script>


    </body>
</html>