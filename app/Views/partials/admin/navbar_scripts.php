<?php if (auth()->loggedIn()): ?>
<script>
$(function () {
    function loadNotifs() {
        $.getJSON('<?= base_url('profile/notifications') ?>', function (res) {
            var items = res.items || [];
            var count = res.count || 0;
            var $badge = $('#navNotifCount');
            if (count > 0) { $badge.text(count).show(); } else { $badge.hide(); }
            $('#navNotifHeader').text(count + ' unread notification(s)');
            var $box = $('#navNotifItems').empty();
            if (!items.length) {
                $box.append('<span class="dropdown-item text-muted">No new notifications</span>');
                return;
            }
            items.forEach(function (n) {
                var $a = $('<a class="dropdown-item" href="#"></a>');
                $a.attr('href', n.url || '#').html('<strong>' + (n.title || '') + '</strong><br><small>' + (n.body || '') + '</small>');
                $box.append($a).append('<div class="dropdown-divider"></div>');
            });
        });
    }
    loadNotifs();
    setInterval(loadNotifs, 45000);
});
</script>
<?php endif ?>
