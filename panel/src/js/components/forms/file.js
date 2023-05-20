import Utils from '../utils';

export default function FileInput(input) {
    const label = $(`label[for="${input.id}"]`);
    const span = $('span', label);

    let isSubmitted = false;

    input.setAttribute('data-label', $(`label[for="${input.id}"] span`).innerHTML);
    input.addEventListener('change', updateLabel);
    input.addEventListener('input', updateLabel);

    input.form.addEventListener('submit', () => {
        if (input.files.length > 0) {
            span.innerHTML += ' <span class="spinner"></span>';
        }
        isSubmitted = true;
    });

    label.addEventListener('drag', preventDefault);
    label.addEventListener('dragstart', preventDefault);
    label.addEventListener('dragend', preventDefault);
    label.addEventListener('dragover', handleDragenter);
    label.addEventListener('dragenter', handleDragenter);
    label.addEventListener('dragleave', handleDragleave);

    label.addEventListener('drop', (event) => {
        event.preventDefault();
        if (isSubmitted) {
            return;
        }
        input.files = event.dataTransfer.files;
        // Firefox won't trigger a change event, so we explicitly do that
        Utils.triggerEvent(input, 'change');
    });

    label.addEventListener('click', (event) => {
        if (isSubmitted) {
            event.preventDefault();
        }
    });

    function updateLabel() {
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
}
