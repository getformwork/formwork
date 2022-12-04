export default function RangeInput(input) {
    input.addEventListener('change', updateValueLabel);
    input.addEventListener('input', updateValueLabel);

    function updateValueLabel() {
        $('output[for="' + this.id + '"]').innerHTML = this.value;
    }
}
