const slides = document.querySelectorAll('.slide');
let index = 0;

function showSlide(n) {
    slides.forEach(slide => slide.classList.remove('active'));
    slides[n].classList.add('active');
}

function nextSlide() {
    index = (index + 1) % slides.length;
    showSlide(index);
}

setInterval(nextSlide, 5000);
const reveals = document.querySelectorAll('.reveal');

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('visible');
        }
    });
}, {
    threshold: 0.15
});

reveals.forEach(el => observer.observe(el));

/* Код для ассистента */

const chatToggle = document.getElementById('chatToggle');
const chatWidget = document.getElementById('chatWidget');
const chatClose = document.getElementById('chatClose');
const chatMessages = document.getElementById('chatMessages');
const chatActions = document.getElementById('chatActions');

const answers = {
    "Как долго ждать заказ?": "Заказ доставляется от 4 до 7 рабочих дней.",
    "Разгрузка/выгрузка бесплатная?": "Да, выгрузка/разгрузка бесплатная, она не входит в стоимость доставки.",
    "Скидка постоянному клиенту?": "Постоянные клиенты получают скидку при заказе оптом 2%.",
    "Можно заказать доставку на определенную дату?": "Да, вы можете запланировать доставку на нужный вам срок, но не позднее 3 недель с подачи заявки.",
    "Оплата сразу или при получении?": "Оплата сразу, только после получения средств мы соберем ваш заказ.",
    "Если приехал брак, что делать?": "Если брак выявился во время принятия товара, то вам необходимо сразу связаться с нами через нашего сотрудника, кто привез вам заказ, мы поможем решить эту проблему.",
    "Как защищается товар при доставке?": "Товар при доставке защищен хорошо. Мы надежно фиксируем все материалы и накрываем тентом. Так что товар доедет к вам целым и сухим.",
    "Не могу дозвониться, что делать?": "Извиняемся за ожидание, если ваш звонок не могут принять, скорее всего нет свободного оператора, вы можете оставить свою заявку и с вами обязательно свяжутся позднее.",
    "При заказе оптом есть скидка?" : "Да, при заказе оптом доставка будет бесплатная.",
    "Почему при заказе нет оплаты доставки?" : "Доставка оплачивается отдельно при получении заказа водителю.",
    "Если по ошибке указал не тот адрес, что делать?" : "Позвонить нам как можно раньше."
};

function openChat() {
    chatWidget.classList.add('open');
    chatWidget.setAttribute('aria-hidden', 'false');
}

function closeChat() {
    chatWidget.classList.remove('open');
    chatWidget.setAttribute('aria-hidden', 'true');
}

function addMessage(text, type) {
    const div = document.createElement('div');
    div.className = type === 'user' ? 'chat-user' : 'chat-bot';
    div.textContent = text;
    chatMessages.appendChild(div);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

function showQuickReplies() {
    chatActions.innerHTML = `
        <button class="chat-btn" data-question="Как долго ждать заказ?">Как долго ждать заказ?</button>
        <button class="chat-btn" data-question="Разгрузка/выгрузка бесплатная?">Разгрузка/выгрузка бесплатная?</button>
        <button class="chat-btn" data-question="Скидка постоянному клиенту?">Скидка постоянному клиенту?</button>
        <button class="chat-btn" data-question="Можно заказать доставку на определенную дату?">Можно заказать доставку на определенную дату?</button>
        <button class="chat-btn" data-question="Оплата сразу или при получении?">Оплата сразу или при получении?</button>
        <button class="chat-btn" data-question="Если приехал брак, что делать?">Если приехал брак, что делать?</button>
        <button class="chat-btn" data-question="Как защищается товар при доставке?">Как защищается товар при доставке?</button>
        <button class="chat-btn" data-question="При заказе оптом есть скидка?">При заказе оптом есть скидка?</button>
        <button class="chat-btn" data-question="Почему при заказе нет оплаты доставки?">Почему при заказе нет оплаты доставки?</button>
        <button class="chat-btn" data-question="Если по ошибке указал не тот адрес, что делать?">Если по ошибке указал не тот адрес, что делать?</button>
    `;
}

chatToggle.addEventListener('click', function (e) {
    e.preventDefault();
    if (chatWidget.classList.contains('open')) {
        closeChat();
    } else {
        openChat();
    }
});

chatClose.addEventListener('click', closeChat);

chatActions.addEventListener('click', function (e) {
    const btn = e.target.closest('button');
    if (!btn) return;

    const question = btn.dataset.question;

    if (btn.dataset.action === 'helper') {
        addMessage('Вызвать помощника', 'user');
        addMessage('Здравствуйте, вас приветствует Ассистент, что вас интересует?', 'bot');
        showQuickReplies();
        return;
    }

    if (question && answers[question]) {
        addMessage(question, 'user');
        addMessage(answers[question], 'bot');
    }
});

// Подтверждение заяки
const contactForm = document.getElementById('contactForm');
const successModal = document.getElementById('successModal');
const successClose = document.getElementById('successClose');
const successOk = document.getElementById('successOk');

function openSuccessModal() {
    successModal.classList.add('open');
    successModal.setAttribute('aria-hidden', 'false');
}

function closeSuccessModal() {
    successModal.classList.remove('open');
    successModal.setAttribute('aria-hidden', 'true');
}

contactForm.addEventListener('submit', function (e) {
    e.preventDefault();
    fetch('send_zayavka.php', { method: 'POST', body: new FormData(contactForm) })
        .then(r => r.json())
        .then(data => {
            if (data.success) { openSuccessModal(); contactForm.reset(); }
            else { alert('Ошибка: ' + data.message); }
        })
        .catch(() => alert('Ошибка соединения. Попробуйте позже.'));
});

successClose.addEventListener('click', closeSuccessModal);
successOk.addEventListener('click', closeSuccessModal);

successModal.addEventListener('click', function (e) {
    if (e.target === successModal) closeSuccessModal();
});

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeSuccessModal();
});

// Личный кабинет
const accountBtn = document.getElementById('accountBtn');
const accountMenu = document.getElementById('accountMenu');

if (accountBtn && accountMenu) {
    accountBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        accountMenu.classList.toggle('open');
        accountBtn.setAttribute('aria-expanded', accountMenu.classList.contains('open'));
    });

    document.addEventListener('click', function (e) {
        if (!accountMenu.contains(e.target) && !accountBtn.contains(e.target)) {
            accountMenu.classList.remove('open');
            accountBtn.setAttribute('aria-expanded', 'false');
        }
    });
}


/* ===== THEME TOGGLE ===== */
(function () {
    const STORAGE_KEY = 'krona-theme';
    const btn = document.getElementById('themeToggle');
    const body = document.body;

    function applyTheme(theme) {
        if (theme === 'light') {
            body.classList.add('light-theme');
        } else {
            body.classList.remove('light-theme');
        }
    }

    // Восстанавливаем сохранённую тему
    const saved = localStorage.getItem(STORAGE_KEY) || 'dark';
    applyTheme(saved);
    document.documentElement.classList.remove('light-theme-pre');

    if (btn) {
        btn.addEventListener('click', function () {
            const isLight = body.classList.toggle('light-theme');
            localStorage.setItem(STORAGE_KEY, isLight ? 'light' : 'dark');
        });
    }
})();
