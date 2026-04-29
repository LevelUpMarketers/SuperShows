(function () {
	const form = document.querySelector('.sd-directory-search__form');
	const resultsContainer = document.querySelector('.sd-directory-results');
	const status = document.querySelector('.sd-directory-status');
	const resetButton = document.querySelector('.sd-directory-search__reset');
	const submitButton = document.querySelector('.sd-directory-search__submit');

	if (!form || !resultsContainer || !status || typeof sdDirectoryParent === 'undefined') {
		return;
	}

	const strings = sdDirectoryParent.strings || {};
	let isFetching = false;

	const setLoading = (isLoading) => {
		resultsContainer.classList.toggle('is-loading', isLoading);
		form.classList.toggle('is-loading', isLoading);
		if (submitButton) {
			submitButton.disabled = isLoading;
		}
	};

	const renderStatus = (message) => {
		status.textContent = message || '';
	};

	const renderCards = (items) => {
		resultsContainer.innerHTML = '';
		(items || []).forEach((item) => {
			const elementTag = item.permalink ? 'a' : 'article';
			const card = document.createElement(elementTag);
			card.className = 'sd-directory-card';
			if (item.permalink) {
				card.href = item.permalink;
			}

			if (item.screenshot) {
				card.classList.add('has-screenshot');
				card.style.setProperty('--sd-card-screenshot', `url("${item.screenshot}")`);
			}

			const logo = document.createElement('div');
			logo.className = 'sd-directory-card__logo';
			if (item.logo) {
				const img = document.createElement('img');
				img.src = item.logo;
				img.alt = item.name ? item.name + ' logo' : '';
				logo.appendChild(img);
			}

			const title = document.createElement('h3');
			title.className = 'sd-directory-card__title';
			title.textContent = item.name || '';

			const meta = document.createElement('p');
			meta.className = 'sd-directory-card__meta';
			meta.textContent = item.meta || '';

			const cta = document.createElement('span');
			cta.className = 'sd-directory-card__cta';
			cta.textContent = strings.view || 'Learn More';

			card.appendChild(logo);
			card.appendChild(title);
			card.appendChild(meta);
			card.appendChild(cta);
			resultsContainer.appendChild(card);
		});

		if (!items || !items.length) {
			renderStatus(strings.noResults || '');
		} else {
			renderStatus('');
		}
	};

	const serializeForm = () => {
		const data = new FormData(form);
		data.append('action', 'supershows_search_tradeshows');
		data.append('nonce', sdDirectoryParent.nonce);
		return data;
	};

	const fetchResults = () => {
		if (isFetching) {
			return;
		}
		isFetching = true;
		setLoading(true);

		fetch(sdDirectoryParent.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: serializeForm()
		})
			.then((response) => response.json())
			.then((json) => {
				if (!json || !json.success || !json.data) {
					throw new Error('Request failed');
				}
				renderCards(json.data.items || []);
			})
			.catch(() => {
				renderStatus(strings.error || '');
			})
			.finally(() => {
				isFetching = false;
				setLoading(false);
			});
	};

	form.addEventListener('submit', function (event) {
		event.preventDefault();
		fetchResults();
	});

	if (resetButton) {
		resetButton.addEventListener('click', function (event) {
			event.preventDefault();
			form.reset();
			fetchResults();
		});
	}
})();
