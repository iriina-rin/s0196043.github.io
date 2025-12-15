document.addEventListener('DOMContentLoaded', function() {
    const popup = document.getElementById("my-dialog");
    const open = document.querySelector(".open-dialog");
    const close = document.querySelector(".close-dialog");
    const form = document.getElementById('my-sweet-form');
    
    function getFormData() {
        return {
            name: document.getElementById('name').value,
            email: document.getElementById('email').value,
            tel: document.getElementById('tel').value,
            organization: document.getElementById('organization').value,
            message: document.getElementById('message').value,
            check: document.getElementById('checkbox').checked
        };
    }

    function closePopup() {
        popup.style.display = "none";

        if (location.hash.includes('#form')) {
            history.back();
        }
    }

    function openPopup() {
        const saved = localStorage.getItem('formData');
        if (saved) {
            const formData = JSON.parse(saved);
            document.getElementById('name').value = formData.name || '';
            document.getElementById('email').value = formData.email || '';
            document.getElementById('tel').value = formData.tel || '';
            document.getElementById('organization').value = formData.organization || '';
            document.getElementById('message').value = formData.message || '';
            document.getElementById('checkbox').checked = formData.check || false;
        }
        
        popup.style.display = "block";
        history.pushState({form: 'open'}, '', '#form');
    }

    open.addEventListener('click', openPopup);

    close.addEventListener('click', closePopup);
    
    window.addEventListener('click', function (event) {
        if (event.target === popup) {
            closePopup();
        }
    });
    
    window.addEventListener('popstate', function (event) {
        if (!location.hash.includes('#form')) {
            closePopup();
        }
    });
    popup.addEventListener('input', function () {
        const formData = getFormData();
        localStorage.setItem('formData', JSON.stringify(formData));
    });

    form.addEventListener('submit', function (event) {
    event.preventDefault();

    const checkbox = document.getElementById('checkbox');
    if (!checkbox.checked) {
        alert('Без согласия политики обработки персональных данных сообщение не может быть отправлено');
        return;
    }

    const formData = new FormData(form);

    fetch('https://formcarry.com/s/YhFifbGcXzg', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.code === 200) {
            alert('Сообщение успешно отправлено');
            localStorage.removeItem('formData');
            form.reset();
            closePopup();
        } else {
            alert('Ошибка отправки');
        }
    })
    .catch(() => {
        alert('Ошибка сети');
    });
});

});
