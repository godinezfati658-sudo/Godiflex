document.addEventListener('DOMContentLoaded', () => {
    const search = document.querySelector('#movie-search');
    const cards = Array.from(document.querySelectorAll('.movie-card'));
    const noResults = document.querySelector('#no-results');

    if (search && cards.length) {
        search.addEventListener('input', () => {
            const query = search.value.trim().toLocaleLowerCase();
            let visibleCount = 0;
            cards.forEach((card) => {
                const matches = card.dataset.title.includes(query);
                card.hidden = !matches;
                if (matches) visibleCount += 1;
            });
            if (noResults) noResults.hidden = visibleCount !== 0;
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === '/' && !['INPUT', 'TEXTAREA'].includes(document.activeElement.tagName)) {
                event.preventDefault();
                search.focus();
            }
            if (event.key === 'Escape' && document.activeElement === search) {
                search.value = '';
                search.dispatchEvent(new Event('input'));
                search.blur();
            }
        });
    }

    const rail = document.querySelector('#movie-rail');
    document.querySelectorAll('[data-scroll]').forEach((button) => {
        button.addEventListener('click', () => {
            if (!rail) return;
            rail.scrollBy({
                left: (button.dataset.scroll === 'right' ? 1 : -1) * Math.max(280, rail.clientWidth * 0.72),
                behavior: 'smooth',
            });
        });
    });
});
