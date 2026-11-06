/**
 * Public site: Lucide icons, smooth scroll, Windy embed.
 * Weather loads from /api/weather/current (see home/index.php inline script).
 */
(function () {
    'use strict';

    function createLucideIcons() {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }

    function wmoWeatherDescription(code) {
        if (code === 0) return 'Clear sky';
        if (code === 1) return 'Mainly clear';
        if (code === 2) return 'Partly cloudy';
        if (code === 3) return 'Overcast';
        if (code >= 45 && code <= 48) return 'Fog';
        if (code >= 51 && code <= 57) return 'Drizzle';
        if (code >= 61 && code <= 67) return 'Rain';
        if (code >= 71 && code <= 77) return 'Snow';
        if (code >= 80 && code <= 82) return 'Rain showers';
        if (code >= 85 && code <= 86) return 'Snow showers';
        if (code >= 95 && code <= 99) return 'Thunderstorm';
        return 'Current conditions';
    }

    function formatWind(kmh) {
        if (typeof kmh !== 'number' || Number.isNaN(kmh)) return '—';
        return Math.round(kmh) + ' km/h';
    }

    function buildWindyEmbedUrl(lat, lon, zoom, overlay) {
        var params = new URLSearchParams({
            lat: String(lat),
            lon: String(lon),
            detailLat: String(lat),
            detailLon: String(lon),
            zoom: String(zoom),
            level: 'surface',
            overlay: overlay || 'wind',
            product: 'ecmwf',
            message: 'true',
            marker: '',
            calendar: 'now',
            pressure: '',
            type: 'map',
            location: 'coordinates',
            detail: 'true',
            metricWind: 'km/h',
            metricTemp: '°C',
            radarRange: '-1'
        });
        return 'https://embed.windy.com/embed2.html?' + params.toString();
    }

    function initFacebookEmbed() {
        var wrap = document.getElementById('drms-fb-embed-wrap');
        var scaler = document.getElementById('drms-fb-embed-scaler');
        var frame = document.getElementById('drms-fb-embed-frame');
        if (!wrap || !scaler || !frame) {
            return;
        }

        var pageUrl = wrap.getAttribute('data-page-url');
        if (!pageUrl) {
            return;
        }

        // Facebook Page Plugin max render width is 500px; scale gently on larger panels (capped).
        var FB_PLUGIN_WIDTH = 500;
        var MAX_SCALE = 1.28;
        var lastHeight = 0;

        function buildSrc(h, pluginW) {
            var params = new URLSearchParams({
                href: pageUrl,
                tabs: 'timeline',
                width: String(pluginW),
                height: String(Math.max(400, Math.floor(h))),
                small_header: 'true',
                adapt_container_width: 'true',
                hide_cover: 'false',
                show_facepile: 'false'
            });
            return 'https://www.facebook.com/plugins/page.php?' + params.toString();
        }

        function resize() {
            var w = wrap.clientWidth || FB_PLUGIN_WIDTH;
            var h = wrap.clientHeight || Math.max(400, window.innerHeight - 280);
            var scale = Math.min(MAX_SCALE, Math.max(0.65, w / FB_PLUGIN_WIDTH));
            var frameH = Math.ceil(h / scale);
            var pluginW = FB_PLUGIN_WIDTH;

            if (Math.abs(frameH - lastHeight) > 8 || frame.src === 'about:blank') {
                frame.src = buildSrc(frameH, pluginW);
                lastHeight = frameH;
            }

            frame.width = pluginW;
            frame.height = frameH;
            scaler.style.width = pluginW + 'px';
            scaler.style.height = frameH + 'px';
            scaler.style.transform = Math.abs(scale - 1) > 0.02 ? 'scale(' + scale + ')' : 'none';
        }

        resize();
        var resizeTimer;
        window.addEventListener('resize', function () {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(resize, 150);
        });
    }

    function initWindyEmbed() {
        var root = document.getElementById('drms-windy');
        var frame = document.getElementById('windyEmbedFrame');
        var btns = document.getElementById('windyOverlayBtns');
        if (!root || !frame || !btns) {
            return;
        }

        var lat = parseFloat(root.getAttribute('data-lat'));
        var lon = parseFloat(root.getAttribute('data-lon'));
        var zoom = parseInt(root.getAttribute('data-zoom'), 10) || 11;
        if (Number.isNaN(lat) || Number.isNaN(lon)) {
            return;
        }

        btns.querySelectorAll('[data-overlay]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var overlay = btn.getAttribute('data-overlay') || 'wind';
                btns.querySelectorAll('[data-overlay]').forEach(function (b) {
                    b.classList.remove('active');
                });
                btn.classList.add('active');
                frame.src = buildWindyEmbedUrl(lat, lon, zoom, overlay);
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        createLucideIcons();

        document.querySelectorAll('a[href*="#"]').forEach(function (anchor) {
            anchor.addEventListener('click', function (e) {
                var href = this.getAttribute('href') || '';
                if (href.indexOf('#') === -1) return;
                try {
                    var u = new URL(href, window.location.href);
                    if (u.pathname !== window.location.pathname || !u.hash || u.hash.length < 2) {
                        return;
                    }
                    var target = document.querySelector(u.hash);
                    if (target) {
                        e.preventDefault();
                        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                } catch (ignore) {
                    /* invalid URL — let browser handle */
                }
            });
        });

        initWindyEmbed();
        initFacebookEmbed();
    });
})();
