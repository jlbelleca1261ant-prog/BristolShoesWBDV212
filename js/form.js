'use strict';

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('contact-form');
    if (!form) return;

    const fields = {
        name: form.elements.name,
        email: form.elements.email,
        phone: form.elements.phone,
        message: form.elements.message
    };
    const errorBox = document.getElementById('contact-error');
    const submitButton = form.querySelector('button[type="submit"]');

    function showFieldError(input, message) {
        const group = input.closest('.form-group');
        group.classList.toggle('field-err', Boolean(message));
        input.setAttribute('aria-invalid', Boolean(message));
        group.querySelector('.field-error').textContent = message;
    }

    function validate() {
        const name = fields.name.value.trim();
        const email = fields.email.value.trim();
        const phone = fields.phone.value.replace(/\s/g, '');
        const message = fields.message.value.trim();
        const errors = {
            name: name.length >= 2 && name.length <= 160 ? '' : 'Enter at least 2 characters.',
            email: /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email) ? '' : 'Enter a valid email address.',
            phone: !phone || /^(\+639|09)\d{9}$/.test(phone) ? '' : 'Use 09XXXXXXXXX or +639XXXXXXXXX.',
            message: message.length >= 10 && message.length <= 5000 ? '' : 'Enter 10 to 5000 characters.'
        };
        Object.keys(fields).forEach(function (key) { showFieldError(fields[key], errors[key]); });
        return Object.values(errors).every(function (error) { return !error; });
    }

    form.addEventListener('input', function () {
        if (!errorBox.hidden) errorBox.hidden = true;
    });

    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        if (!validate()) return;
        errorBox.hidden = true;
        submitButton.disabled = true;
        submitButton.textContent = 'Sending…';

        try {
            const response = await fetch('process_contact.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-CSRF-Token': window.BRISTOL_CSRF},
                body: JSON.stringify({
                    name: fields.name.value.trim(),
                    email: fields.email.value.trim(),
                    phone: fields.phone.value.replace(/\s/g, ''),
                    message: fields.message.value.trim()
                })
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.error || 'Could not save your message.');
            form.style.display = 'none';
            document.getElementById('form-success').style.display = 'block';
        } catch (error) {
            errorBox.textContent = error.message || 'Network error. Please try again.';
            errorBox.hidden = false;
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = 'Send Message';
        }
    });
});
