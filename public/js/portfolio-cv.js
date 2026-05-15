if (window.location.search.indexOf('print=1') !== -1) {
    window.addEventListener('load', function () {
        window.print();
    });
}
