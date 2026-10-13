(function () {
    const setNavbarState = () => {
        const nav = document.querySelector('.navbar');
        if (!nav) return;
        nav.classList.toggle('navbar-scrolled', window.scrollY > 40);
    };

    window.addEventListener('scroll', setNavbarState, { passive: true });
    window.addEventListener('load', setNavbarState);
})();
