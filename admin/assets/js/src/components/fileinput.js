Formwork.FileInput = function (input) {
    var label = $('label[for="' + input.id + '"]');

    input.setAttribute('data-label', $('label[for="' + input.id + '"] span').innerHTML);
    input.addEventListener('change', updateLabel);
    input.addEventListener('input', updateLabel);

    label.addEventListener('drag', preventDefault);
    label.addEventListener('dragstart', preventDefault);
    label.addEventListener('dragend', preventDefault);
    label.addEventListener('dragover', handleDragenter);
    label.addEventListener('dragenter', handleDragenter);
    label.addEventListener('dragleave', handleDragleave);

    label.addEventListener('drop', function (event) {
        var target = document.getElementById(this.getAttribute('for'));
        target.files = event.dataTransfer.files;
        // Firefox won't trigger a change event, so we explicitly do that
        Formwork.Utils.triggerEvent(target, 'change');
        event.preventDefault();
    });

    function updateLabel() {
        var span = $('label[for="' + this.id + '"] span');
        if (this.files.length > 0) {
            span.innerHTML = this.files[0].name;
        } else {
            span.innerHTML = this.getAttribute('data-label');
        }
    }

    function preventDefault(event) {
        event.preventDefault();
    }

    function handleDragenter(event) {
        this.classList.add('drag');
        event.preventDefault();
    }

    function handleDragleave(event) {
        this.classList.remove('drag');
        event.preventDefault();
    }
};
