(function () {
	const form = document.querySelector('.sd-directory-search__form');
	const resultsContainer = document.querySelector('.sd-directory-results');
	const status = document.querySelector('.sd-directory-status');
	const resetButton = document.querySelector('.sd-directory-search__reset');
	const submitButton = document.querySelector('.sd-directory-search__submit');
	let loadSentinel = document.querySelector('.sd-directory-load-sentinel');
	let loadIndicator = loadSentinel ? loadSentinel.querySelector('.sd-directory-load-indicator') : null;

	if (!form || !resultsContainer || !status || typeof sdDirectoryParent === 'undefined') {
		return;
	}

	const strings = sdDirectoryParent.strings || {};
	const perPage = parseInt(sdDirectoryParent.perPage || '9', 10) || 9;
	let currentPage = 1;
	let totalPages = 1;
	let isFetching = false;
	let observer;

	const ensureSentinel = () => {
		if (!loadSentinel) {
			loadSentinel = document.createElement('div');
			loadSentinel.className = 'sd-directory-load-sentinel';
			loadIndicator = document.createElement('div');
			loadIndicator.className = 'sd-directory-load-indicator';
			loadSentinel.appendChild(loadIndicator);
		}
		if (!resultsContainer.contains(loadSentinel)) {
			resultsContainer.appendChild(loadSentinel);
		}
		return loadSentinel;
	};

	const setLoading = (isLoading) => {
		resultsContainer.classList.toggle('is-loading', isLoading);
		if (submitButton) submitButton.disabled = isLoading;
	};
	const setLoadingMore = (isLoading) => {
		resultsContainer.classList.toggle('is-loading-more', isLoading);
		if (loadIndicator) loadIndicator.style.opacity = isLoading ? '1' : '0';
	};
	const renderStatus = (message) => { status.textContent = message || ''; };

	const renderCards = (items, append) => {
		const sentinel = ensureSentinel();
		if (!append) {
			resultsContainer.innerHTML = '';
			resultsContainer.appendChild(sentinel);
		}
		(items || []).forEach((item) => {
			const card = document.createElement(item.permalink ? 'a' : 'article');
			card.className = 'sd-directory-card';
			if (item.permalink) card.href = item.permalink;
			if (item.screenshot) { card.classList.add('has-screenshot'); card.style.setProperty('--sd-card-screenshot', `url("${item.screenshot}")`); }
			card.innerHTML = `<div class="sd-directory-card__logo">${item.logo ? `<img src="${item.logo}" alt="${(item.name||'')} logo">` : ''}</div><h3 class="sd-directory-card__title">${item.name||''}</h3><p class="sd-directory-card__meta">${item.meta||''}</p><span class="sd-directory-card__cta">${strings.view || 'Learn More'}</span>`;
			resultsContainer.insertBefore(card, sentinel);
		});
		if (!append) renderStatus(items && items.length ? '' : (strings.noResults || ''));
	};

	const updateInfinite = () => {
		const sentinel = ensureSentinel();
		if (!observer) {
			observer = new IntersectionObserver((entries) => {
				entries.forEach((entry) => {
					if (entry.isIntersecting && !isFetching && currentPage < totalPages) fetchResults(currentPage + 1, true);
				});
			}, { rootMargin: '320px 0px' });
		}
		observer.disconnect();
		if (currentPage < totalPages) { sentinel.style.display = 'flex'; observer.observe(sentinel); } else { sentinel.style.display = 'none'; }
	};

	const serializeForm = () => {
		const data = new FormData(form);
		data.append('action', 'supershows_search_tradeshows');
		data.append('nonce', sdDirectoryParent.nonce);
		data.append('per_page', String(perPage));
		return data;
	};

	const fetchResults = (page, append) => {
		if (isFetching) return;
		isFetching = true;
		append ? setLoadingMore(true) : setLoading(true);
		const data = serializeForm();
		data.append('page', String(page));
		fetch(sdDirectoryParent.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: data })
			.then((r) => r.json())
			.then((json) => {
				if (!json || !json.success || !json.data) throw new Error('Request failed');
				currentPage = parseInt(json.data.page || page, 10) || page;
				totalPages = parseInt(json.data.total_pages || 1, 10) || 1;
				renderCards(json.data.items || [], append);
				updateInfinite();
			})
			.catch(() => {
				renderStatus(strings.error || '');
			})
			.finally(() => {
				isFetching = false;
				setLoading(false);
				setLoadingMore(false);
			});
	};

	form.addEventListener('submit', (e) => { e.preventDefault(); fetchResults(1, false); });
	if (resetButton) resetButton.addEventListener('click', (e) => { e.preventDefault(); form.reset(); fetchResults(1, false); });
	updateInfinite();
})();
