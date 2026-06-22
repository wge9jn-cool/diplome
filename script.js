document.addEventListener("DOMContentLoaded", () => {
    const smoothScrollLinks = document.querySelectorAll("[data-scroll-to], .nav__link[href^='#'], .footer__links a[href^='#']");

    smoothScrollLinks.forEach((el) => {
        el.addEventListener("click", (e) => {
            const target = el.dataset.scrollTo || el.getAttribute("href");
            if (!target || !target.startsWith("#")) return;
            const targetEl = document.querySelector(target);
            if (!targetEl) return;
            e.preventDefault();
            const offset = 64;
            const rect = targetEl.getBoundingClientRect();
            const scrollTop = window.scrollY || window.pageYOffset;
            const top = rect.top + scrollTop - offset;
            window.scrollTo({ top, behavior: "smooth" });
        });
    });

    const burger = document.querySelector(".header__burger");
    const nav = document.querySelector(".nav");

    function setNavOpen(open) {
        if (!nav || !burger) return;
        nav.classList.toggle("nav--open", open);
        burger.setAttribute("aria-expanded", open ? "true" : "false");
        document.body.classList.toggle("nav-open", open);
    }

    function isNavUiTarget(target) {
        if (!(target instanceof Element)) return false;
        return Boolean(target.closest(".header__burger, .nav"));
    }

    if (burger && nav) {
        let navIgnoreOutsideUntil = 0;

        burger.addEventListener("click", (e) => {
            e.stopPropagation();
            const opening = !nav.classList.contains("nav--open");
            setNavOpen(opening);
            if (opening) {
                navIgnoreOutsideUntil = Date.now() + 350;
            }
        });

        nav.querySelectorAll("a").forEach((link) => {
            link.addEventListener("click", () => {
                setNavOpen(false);
            });
        });

        document.addEventListener("click", (e) => {
            if (!nav.classList.contains("nav--open")) return;
            if (Date.now() < navIgnoreOutsideUntil) return;
            if (isNavUiTarget(e.target)) return;
            setNavOpen(false);
        });

        window.addEventListener("resize", () => {
            if (window.innerWidth > 960) {
                setNavOpen(false);
            }
        });
    }

    // Универсальные модальные окна (используются в кабинете для списков обращений)
    const modalOpeners = document.querySelectorAll("[data-modal-open]");
    const body = document.body;
    let lastFocused = null;

    function openModal(modalEl) {
        if (!modalEl) return;
        lastFocused = document.activeElement;
        modalEl.classList.add("modal--open");
        modalEl.setAttribute("aria-hidden", "false");
        body.style.overflow = "hidden";
        const closeBtn = modalEl.querySelector("[data-modal-close]");
        if (closeBtn) closeBtn.focus();
    }

    function closeModal(modalEl) {
        if (!modalEl) return;
        modalEl.classList.remove("modal--open");
        modalEl.setAttribute("aria-hidden", "true");
        body.style.overflow = "";
        if (lastFocused && typeof lastFocused.focus === "function") {
            lastFocused.focus();
        }
        lastFocused = null;
    }

    modalOpeners.forEach((btn) => {
        btn.addEventListener("click", () => {
            const target = btn.getAttribute("data-modal-open");
            if (!target) return;
            const modalEl = document.querySelector(target);
            openModal(modalEl);
        });
    });

    document.querySelectorAll(".modal").forEach((modalEl) => {
        modalEl.querySelectorAll("[data-modal-close]").forEach((el) => {
            el.addEventListener("click", () => closeModal(modalEl));
        });
    });

    const hashModalId = window.location.hash;
    if (hashModalId && hashModalId.startsWith("#svcReview-")) {
        const hashModal = document.querySelector(hashModalId);
        if (hashModal && hashModal.classList.contains("modal")) {
            openModal(hashModal);
        }
    }

    document.addEventListener("keydown", (e) => {
        if (e.key !== "Escape") return;
        const opened = document.querySelector(".modal.modal--open");
        if (opened) closeModal(opened);
    });

    // Автоматическое заполнение и зацикливание строки услуг под баннером
    const heroTrack = document.querySelector(".hero-tags__track");
    if (heroTrack) {
        const baseInner = heroTrack.querySelector(".hero-tags__inner");
        if (baseInner) {
            const baseHTML = baseInner.innerHTML;
            const minWidth = window.innerWidth * 2;
            while (heroTrack.scrollWidth < minWidth) {
                const clone = document.createElement("div");
                clone.className = "hero-tags__inner";
                clone.innerHTML = baseHTML;
                heroTrack.appendChild(clone);
            }

            let offset = 0;
            const speed = 0.5;

            function tick() {
                offset -= speed;
                const totalWidth = heroTrack.scrollWidth / 2;
                if (Math.abs(offset) >= totalWidth) {
                    offset = 0;
                }
                heroTrack.style.transform = `translateX(${offset}px)`;
                requestAnimationFrame(tick);
            }

            requestAnimationFrame(tick);
        }
    }

    const calcBuilder = document.getElementById("calcBuilder");

    function formatPrice(value) {
        return value.toLocaleString("ru-RU", {
            style: "currency",
            currency: "RUB",
            maximumFractionDigits: 0,
        });
    }

    if (calcBuilder) {
        const servicesRoot = document.getElementById("calcServices");
        const cartRoot = document.getElementById("calcCart");
        const totalEl = document.getElementById("calcTotal");
        const warningsEl = document.getElementById("calcWarnings");
        const itemsJsonEl = document.getElementById("calcItemsJson");
        const totalInputEl = document.getElementById("calcTotalInput");
        const checkoutForm = document.getElementById("calcCheckout");
        const payBtn = document.getElementById("calcPayBtn");
        const callBtn = document.getElementById("calcCallBtn");
        const actionEl = document.getElementById("calcAction");

        const catalog = Array.isArray(window.__CALC_CATALOG) && window.__CALC_CATALOG.length
            ? window.__CALC_CATALOG
            : [];

        /** cart items:
         * { key, serviceId, serviceTitle, variantId, variantTitle, kind, unitPrice, qty, meta, requiresContact, extra: { urgency?: true } }
         */
        let cart = [];

        function findService(serviceId) {
            return catalog.find((s) => s.id === serviceId) || null;
        }

        function calcItemPrice(item) {
            let base = item.unitPrice * item.qty;
            if (item.extra && item.extra.urgency) {
                base = Math.round(base * 1.3);
            }
            return base;
        }

        function cartTotal() {
            return cart.reduce((s, it) => s + calcItemPrice(it), 0);
        }

        function cartTotalPayable() {
            return cart
                .filter((it) => it.kind !== "quote")
                .reduce((s, it) => s + calcItemPrice(it), 0);
        }

        function hasQuoteInCart() {
            return cart.some((it) => it.kind === "quote");
        }

        function hasComplex() {
            return cart.some((it) => !!it.requiresContact);
        }

        function renderServices() {
            if (!servicesRoot) return;
            servicesRoot.innerHTML = "";

            catalog.forEach((svc, svcIndex) => {
                const wrap = document.createElement("details");
                wrap.className = "calc-service calc-service--acc";
                if (svcIndex === 0) wrap.setAttribute("open", "open");

                wrap.addEventListener("toggle", () => {
                    if (!wrap.open) return;
                    servicesRoot.querySelectorAll("details.calc-service--acc").forEach((d) => {
                        if (d !== wrap) d.removeAttribute("open");
                    });
                });

                const summary = document.createElement("summary");
                summary.className = "calc-service__summary";
                const sumTitle = document.createElement("span");
                sumTitle.className = "calc-service__summary-title";
                sumTitle.textContent = svc.title;
                summary.appendChild(sumTitle);
                if (svc.hint) {
                    const sumMeta = document.createElement("span");
                    sumMeta.className = "calc-service__summary-meta";
                    sumMeta.textContent = svc.hint;
                    summary.appendChild(sumMeta);
                }

                const inner = document.createElement("div");
                inner.className = "calc-service__inner";
                const actions = document.createElement("div");
                actions.className = "calc-service__actions";

                if (svc.kind === "quote") {
                    const select = document.createElement("select");
                    select.className = "calc-service__select";
                    svc.variants.forEach((v) => {
                        const opt = document.createElement("option");
                        opt.value = v.id;
                        opt.textContent = v.title;
                        select.appendChild(opt);
                    });

                    const btn = document.createElement("button");
                    btn.type = "button";
                    btn.className = "btn btn--secondary btn--calc-add";
                    btn.textContent = "В корзину";
                    btn.addEventListener("click", () => {
                        const varId = select.value;
                        const variant = svc.variants.find((v) => v.id === varId);
                        const key = `${svc.id}:${varId}`;
                        if (cart.some((c) => c.key === key)) {
                            return;
                        }
                        cart.push({
                            key,
                            serviceId: svc.id,
                            serviceTitle: svc.title,
                            variantId: varId,
                            variantTitle: variant ? variant.title : "",
                            kind: "quote",
                            unitPrice: 0,
                            qty: 1,
                            meta: "",
                            requiresContact: true,
                            extra: {},
                        });
                        renderCart();
                    });

                    const tb = document.createElement("div");
                    tb.className = "calc-service__toolbar";

                    const c1 = document.createElement("div");
                    c1.className = "calc-service__cell calc-service__cell--grow";
                    const l1 = document.createElement("span");
                    l1.className = "calc-service__lbl";
                    l1.textContent = "Категория товара";
                    c1.appendChild(l1);
                    c1.appendChild(select);

                    const c3 = document.createElement("div");
                    c3.className = "calc-service__cell calc-service__cell--btn";
                    c3.appendChild(btn);

                    tb.appendChild(c1);
                    tb.appendChild(c3);
                    actions.appendChild(tb);

                    if (svc.warning) {
                        const note = document.createElement("p");
                        note.className = "calc-service__note";
                        note.textContent = svc.warning;
                        inner.appendChild(note);
                    }
                } else if (svc.kind === "hourly") {
                    const select = document.createElement("select");
                    select.className = "calc-service__select";
                    svc.variants.forEach((v) => {
                        const opt = document.createElement("option");
                        opt.value = v.id;
                        opt.textContent = `${v.title} — ${formatPrice(v.rate)}/час`;
                        select.appendChild(opt);
                    });

                    const hours = document.createElement("input");
                    hours.type = "number";
                    hours.min = "1";
                    hours.max = "8";
                    hours.step = "1";
                    hours.value = "1";

                    const btn = document.createElement("button");
                    btn.type = "button";
                    btn.className = "btn btn--secondary btn--calc-add";
                    btn.textContent = "В корзину";
                    btn.addEventListener("click", () => {
                        const varId = select.value;
                        const variant = svc.variants.find((v) => v.id === varId);
                        const h = Math.max(1, Math.min(8, Number(hours.value || 1)));
                        const key = `${svc.id}:${varId}`;
                        const existing = cart.find((c) => c.key === key);
                        if (existing) {
                            existing.qty = Math.max(1, Math.min(8, existing.qty + h));
                        } else {
                            cart.push({
                                key,
                                serviceId: svc.id,
                                serviceTitle: svc.title,
                                variantId: varId,
                                variantTitle: variant ? variant.title : "",
                                kind: "hourly",
                                unitPrice: variant ? variant.rate : 880,
                                qty: h,
                                meta: "час(ов)",
                                requiresContact: false,
                                extra: {},
                            });
                        }
                        renderCart();
                    });

                    const tb = document.createElement("div");
                    tb.className = "calc-service__toolbar";

                    const c1 = document.createElement("div");
                    c1.className = "calc-service__cell calc-service__cell--grow";
                    const l1 = document.createElement("span");
                    l1.className = "calc-service__lbl";
                    l1.textContent = "Тип товара";
                    c1.appendChild(l1);
                    c1.appendChild(select);

                    const c2 = document.createElement("div");
                    c2.className = "calc-service__cell calc-service__cell--narrow";
                    const l2 = document.createElement("span");
                    l2.className = "calc-service__lbl";
                    l2.textContent = "Часы";
                    c2.appendChild(l2);
                    c2.appendChild(hours);

                    const c3 = document.createElement("div");
                    c3.className = "calc-service__cell calc-service__cell--btn";
                    c3.appendChild(btn);

                    tb.appendChild(c1);
                    tb.appendChild(c2);
                    tb.appendChild(c3);
                    actions.appendChild(tb);
                } else {
                    const select = document.createElement("select");
                    select.className = "calc-service__select";
                    svc.variants.forEach((v) => {
                        const opt = document.createElement("option");
                        opt.value = v.id;
                        opt.textContent = v.title;
                        select.appendChild(opt);
                    });

                    const extraWrap = document.createElement("div");
                    if (svc.extras && svc.extras.length) {
                        const ex = svc.extras[0];
                        extraWrap.className = "calc-service__extras";
                        extraWrap.innerHTML = `
                            <label class="checkbox">
                                <input type="checkbox" data-extra="${ex.id}" />
                                <span>${ex.title}</span>
                            </label>
                        `;
                    }

                    const btn = document.createElement("button");
                    btn.type = "button";
                    btn.className = "btn btn--secondary btn--calc-add";
                    btn.textContent = "В корзину";
                    btn.addEventListener("click", () => {
                        const varId = select.value;
                        const variant = svc.variants.find((v) => v.id === varId);
                        if (!variant) return;

                        if (variant.once) {
                            const already = cart.some((c) => c.serviceId === svc.id && c.variantId === varId);
                            if (already) return;
                        }

                        const urgencyChecked = extraWrap.querySelector('input[type="checkbox"]')?.checked || false;
                        const key = `${svc.id}:${varId}:${urgencyChecked ? "u1" : "u0"}`;
                        const existing = cart.find((c) => c.key === key);
                        if (existing && (variant.allowQty || (svc.extras && svc.extras.length))) {
                            existing.qty = Math.min(10, existing.qty + 1);
                        } else if (!existing) {
                            cart.push({
                                key,
                                serviceId: svc.id,
                                serviceTitle: svc.title,
                                variantId: varId,
                                variantTitle: variant.title,
                                kind: "fixed",
                                unitPrice: variant.price,
                                qty: 1,
                                meta: "",
                                requiresContact: !!variant.requiresContact,
                                extra: { urgency: urgencyChecked },
                                allowQty: !!variant.allowQty,
                            });
                        }
                        renderCart();
                    });

                    const tb = document.createElement("div");
                    tb.className = "calc-service__toolbar";

                    const grow = document.createElement("div");
                    grow.className = "calc-service__cell calc-service__cell--grow";
                    const lv = document.createElement("span");
                    lv.className = "calc-service__lbl";
                    lv.textContent = "Вариант";
                    grow.appendChild(lv);
                    grow.appendChild(select);

                    const btnCell = document.createElement("div");
                    btnCell.className = "calc-service__cell calc-service__cell--btn";
                    btnCell.appendChild(btn);

                    tb.appendChild(grow);
                    if (extraWrap.innerHTML) tb.appendChild(extraWrap);
                    tb.appendChild(btnCell);
                    actions.appendChild(tb);
                }

                inner.appendChild(actions);
                wrap.appendChild(summary);
                wrap.appendChild(inner);
                servicesRoot.appendChild(wrap);
            });
        }

        function renderCart() {
            if (!cartRoot) return;
            cartRoot.innerHTML = "";

            if (!cart.length) {
                const empty = document.createElement("div");
                empty.className = "calc-cart__empty";
                empty.textContent = "Пока пусто. Откройте раздел в списке услуг и нажмите «В корзину».";
                cartRoot.appendChild(empty);
            } else {
                cart.forEach((it) => {
                    const item = document.createElement("div");
                    item.className = "calc-cart__item";
                    const isQuote = it.kind === "quote";
                    const price = calcItemPrice(it);
                    const sub = it.kind === "hourly" ? `${it.qty} ${it.meta}` : it.qty > 1 ? `×${it.qty}` : "";
                    const urgency = it.extra?.urgency ? " + срочно (+30%)" : "";
                    const priceBlock = isQuote
                        ? '<div class="calc-cart__price calc-cart__price--quote">по договорённости</div>'
                        : `<div class="calc-cart__price">${formatPrice(price)}</div>`;
                    item.innerHTML = `
                        <div class="calc-cart__top">
                            <div>
                                <div class="calc-cart__name">${it.variantTitle}${urgency}</div>
                                <div class="calc-cart__meta">${it.serviceTitle}${sub ? " • " + sub : ""}</div>
                            </div>
                            <div class="calc-cart__right">
                                ${priceBlock}
                            </div>
                        </div>
                        <div class="calc-cart__controls"></div>
                    `;
                    const controls = item.querySelector(".calc-cart__controls");

                    const qtyWrap = document.createElement("div");
                    qtyWrap.className = "calc-cart__qty";
                    const label = document.createElement("span");
                    label.textContent = it.kind === "hourly" ? "Часы:" : "Кол-во:";
                    const input = document.createElement("input");
                    input.type = "number";
                    input.min = "1";
                    input.max = it.kind === "hourly" ? "8" : "10";
                    input.step = "1";
                    input.value = String(it.qty);
                    input.addEventListener("change", () => {
                        let v = Number(input.value || 1);
                        if (!Number.isFinite(v)) v = 1;
                        v = Math.max(1, Math.min(it.kind === "hourly" ? 8 : 10, v));
                        it.qty = v;
                        renderCart();
                    });

                    // qty editable for hourly or allowQty or duplicates
                    const qtyEditable = !isQuote && (it.kind === "hourly" || !!it.allowQty);
                    if (qtyEditable) {
                        qtyWrap.appendChild(label);
                        qtyWrap.appendChild(input);
                        controls.appendChild(qtyWrap);
                    }

                    const remove = document.createElement("button");
                    remove.type = "button";
                    remove.className = "calc-cart__remove";
                    remove.textContent = "Удалить";
                    remove.addEventListener("click", () => {
                        cart = cart.filter((c) => c.key !== it.key);
                        renderCart();
                    });
                    controls.appendChild(remove);

                    cartRoot.appendChild(item);
                });
            }

            const quoteMode = hasQuoteInCart();
            const payableTotal = cartTotalPayable();
            if (totalEl) {
                totalEl.classList.toggle("calc__price--quote", quoteMode && payableTotal === 0);
                if (quoteMode && payableTotal === 0) {
                    totalEl.textContent = "Стоимость уточняется специалистом";
                } else if (quoteMode) {
                    totalEl.textContent = `${formatPrice(payableTotal)} + экспертиза по договорённости`;
                } else {
                    totalEl.textContent = formatPrice(payableTotal);
                }
            }
            if (totalInputEl) totalInputEl.value = String(payableTotal);
            if (itemsJsonEl) {
                const payload = cart.map((it) => ({
                    service_id: it.serviceId,
                    variant_id: it.variantId,
                    qty: it.qty,
                    urgency: !!it.extra?.urgency,
                    kind: it.kind,
                }));
                itemsJsonEl.value = JSON.stringify(payload);
            }

            const warnings = [];
            if (quoteMode) {
                warnings.push(
                    "Товароведческая экспертиза: стоимость не рассчитывается онлайн. Доступно только оформление с оплатой по договорённости."
                );
            }
            if (cart.some((it) => it.kind === "hourly")) {
                warnings.push("Почасовые позиции: окончательная стоимость может уточняться по факту работ.");
            }
            if (warningsEl) warningsEl.textContent = warnings.join(" ");

            const complex = hasComplex();
            const yookassaRadio = checkoutForm
                ? checkoutForm.querySelector('input[name="payment_method"][value="yookassa"]')
                : null;
            const laterRadio = checkoutForm
                ? checkoutForm.querySelector('input[name="payment_method"][value="later"]')
                : null;
            if (yookassaRadio) {
                yookassaRadio.disabled = quoteMode;
                const yLabel = yookassaRadio.closest("label");
                if (yLabel) {
                    yLabel.style.opacity = quoteMode ? "0.45" : "";
                    yLabel.style.pointerEvents = quoteMode ? "none" : "";
                }
            }
            if (laterRadio && quoteMode) {
                laterRadio.checked = true;
            }
            if (payBtn) payBtn.style.display = quoteMode ? "none" : "";
            if (callBtn) {
                callBtn.textContent = quoteMode
                    ? "Оформить заявку"
                    : complex
                      ? "Отправить заявку"
                      : "Заказать звонок";
                callBtn.classList.toggle("btn--primary", quoteMode);
                callBtn.classList.toggle("btn--secondary", !quoteMode);
            }
        }

        function setActionAndSubmit(action) {
            if (!checkoutForm) return;
            if (actionEl) actionEl.value = action;
            checkoutForm.requestSubmit ? checkoutForm.requestSubmit() : checkoutForm.submit();
        }

        if (callBtn) {
            callBtn.addEventListener("click", () => {
                // отправка заявки (без онлайн-оплаты)
                setActionAndSubmit("later");
            });
        }

        function syncCalcCheckoutPayButton() {
            if (!checkoutForm || !payBtn) return;
            const pm = checkoutForm.querySelector('input[name="payment_method"]:checked');
            payBtn.textContent =
                pm && pm.value === "later" ? "Продолжить" : "Оплатить через ЮKassa";
        }

        if (checkoutForm) {
            checkoutForm.querySelectorAll('input[name="payment_method"]').forEach((radio) => {
                radio.addEventListener("change", syncCalcCheckoutPayButton);
            });
            syncCalcCheckoutPayButton();

            checkoutForm.addEventListener("submit", (e) => {
                if (!cart.length) {
                    e.preventDefault();
                    return;
                }
                const pm = checkoutForm.querySelector('input[name="payment_method"]:checked');
                if (actionEl && pm) {
                    actionEl.value =
                        hasQuoteInCart() || pm.value !== "yookassa" ? "later" : "yookassa";
                }
            });
        }

        renderServices();
        renderCart();
    }

    // Модалка новостей (старый механизм)

    const faqList = document.getElementById("faqList");
    if (faqList) {
        faqList.addEventListener("click", (e) => {
            const btn = e.target.closest(".faq__question");
            if (!btn) return;
            const item = btn.closest(".faq__item");
            if (!item) return;

            const isOpen = item.classList.contains("faq__item--open");
            faqList.querySelectorAll(".faq__item").forEach((i) => {
                i.classList.remove("faq__item--open");
                const ans = i.querySelector(".faq__answer");
                if (ans) ans.style.maxHeight = "0px";
            });

            if (!isOpen) {
                item.classList.add("faq__item--open");
                const answer = item.querySelector(".faq__answer");
                if (answer) answer.style.maxHeight = answer.scrollHeight + "px";
            }
        });

        faqList.querySelectorAll(".faq__item").forEach((item, index) => {
            if (index === 0) {
                item.classList.add("faq__item--open");
                const answer = item.querySelector(".faq__answer");
                if (answer) answer.style.maxHeight = answer.scrollHeight + "px";
            }
        });
    }

    document.querySelectorAll("[data-open-request]").forEach((btn) => {
        btn.addEventListener("click", () => {
            const service = btn.getAttribute("data-service");
            const select = document.getElementById("requestService");
            if (select && service) {
                select.value = service;
            }
            const section = document.getElementById("request");
            if (section) {
                const offset = 64;
                const rect = section.getBoundingClientRect();
                const scrollTop = window.scrollY || window.pageYOffset;
                const top = rect.top + scrollTop - offset;
                window.scrollTo({ top, behavior: "smooth" });
            }
        });
    });

    // ── Модальное окно новостей ──────────────────────────────────
    const modal        = document.getElementById("newsModal");
    const modalOverlay = document.getElementById("newsModalOverlay");
    const modalClose   = document.getElementById("newsModalClose");
    const modalDate    = document.getElementById("newsModalDate");
    const modalTitle   = document.getElementById("newsModalTitle");
    const modalBody    = document.getElementById("newsModalBody");

    if (modal) {
        function openModal(title, date, body) {
            modalDate.textContent  = date;
            modalTitle.textContent = title;
            modalBody.textContent  = body;
            modal.classList.add("is-open");
            document.body.style.overflow = "hidden";
            modalClose.focus();
        }

        function closeModal() {
            modal.classList.remove("is-open");
            document.body.style.overflow = "";
        }

        document.querySelectorAll(".news-tile[data-modal-title]").forEach((tile) => {
            tile.addEventListener("click", () => {
                openModal(
                    tile.dataset.modalTitle,
                    tile.dataset.modalDate,
                    tile.dataset.modalBody
                );
            });
        });

        modalClose.addEventListener("click", closeModal);
        modalOverlay.addEventListener("click", closeModal);

        document.addEventListener("keydown", (e) => {
            if (e.key === "Escape" && modal.classList.contains("is-open")) {
                closeModal();
            }
        });
    }

    document.querySelectorAll(".file-upload__input").forEach((input) => {
        const root = input.closest(".file-upload");
        const nameEl = root?.querySelector(".file-upload__name");
        if (!root || !nameEl) return;

        const emptyText = nameEl.dataset.empty || "Файл не выбран";

        const sync = () => {
            const file = input.files && input.files[0];
            if (file) {
                nameEl.textContent = file.name;
                nameEl.classList.add("file-upload__name--chosen");
                root.classList.add("file-upload--chosen");
            } else {
                nameEl.textContent = emptyText;
                nameEl.classList.remove("file-upload__name--chosen");
                root.classList.remove("file-upload--chosen");
            }
        };

        input.addEventListener("change", sync);
    });
});

