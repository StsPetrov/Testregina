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
    </body>
</html>