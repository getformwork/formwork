export default function ImagePicker(element) {
    var options = $$('option', element);
    var confirmCommand = $('.image-picker-confirm', element.parentNode);
    var uploadCommand = $('[data-command=upload]', element.parentNode);

    var container, thumbnail, i;

    element.style.display = 'none';

    if (options.length > 0) {
        container = document.createElement('div');
        container.className = 'image-picker-thumbnails';

        for (i = 0; i < options.length; i++) {
            thumbnail = document.createElement('div');
            thumbnail.className = 'image-picker-thumbnail';
            thumbnail.style.backgroundImage = 'url(' + options[i].value + ')';
            thumbnail.setAttribute('data-uri', options[i].value);
            thumbnail.setAttribute('data-filename', options[i].text);
            thumbnail.addEventListener('click', handleThumbnailClick);
            thumbnail.addEventListener('dblclick', handleThumbnailDblclick);
            container.appendChild(thumbnail);
        }

        element.parentNode.insertBefore(container, element);
        $('.image-picker-empty-state').style.display = 'none';
    }

    confirmCommand.addEventListener('click', function () {
        var selectedThumbnail = $('.image-picker-thumbnail.selected');
        var target = document.getElementById(this.getAttribute('data-target'));
        if (selectedThumbnail && target) {
            target.value = selectedThumbnail.getAttribute('data-filename');
        }
    });

    uploadCommand.addEventListener('click', function () {
        document.getElementById(this.getAttribute('data-upload-target')).click();
    });

    function handleThumbnailClick() {
        var target = document.getElementById($('.image-picker-confirm').getAttribute('data-target'));
        if (target) {
            target.value = this.getAttribute('data-filename');
        }
        $$('.image-picker-thumbnail').forEach(function (element) {
            element.classList.remove('selected');
        });
        this.classList.add('selected');
    }

    function handleThumbnailDblclick() {
        this.click();
        $('.image-picker-confirm').click();
    }
}
