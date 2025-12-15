document.addEventListener('DOMContentLoaded', function () {

    const form = document.getElementById('my-sweet-form');

    form.addEventListener('submit', function (event) {
        event.preventDefault();

        if (!document.getElementById('checkbox').checked) {
            alert('Поставь галочку согласия');
            return;
        }

        const formData = new FormData(form);

        fetch('https://formcarry.com/s/YhFifbGcXzg', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.code === 200) {
                alert('ОТПРАВЛЕНО');
                form.reset();
            } else {
                alert('ОШИБКА');
            }
        })
        .catch(() => alert('СЕТЬ НЕ РАБОТАЕТ'));
    });

});
