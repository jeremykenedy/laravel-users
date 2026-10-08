<script>
document.addEventListener('click', function (event) {
    const close = event.target.closest('#laravelusers [data-lu-dismiss-alert]');
    if (close) close.closest('.lu-flash').remove();
});
</script>
