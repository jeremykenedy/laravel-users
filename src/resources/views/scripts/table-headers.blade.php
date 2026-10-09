const headers = Array.from(table.tHead.rows[0].cells).flatMap(function (header) {
    if (!header.hasAttribute('data-lu-avatar-label')) return [header];
    const avatar = document.createElement('th');
    avatar.dataset.luLabel = header.dataset.luAvatarLabel;
    avatar.dataset.luNoSort = '';
    return [avatar, header];
});
