document.addEventListener('DOMContentLoaded', () => {
  if (!Array.isArray(fybs_data)) return;

  fybs_data.forEach(({ class: cssClass, images, duration }) => {
    const elements = document.querySelectorAll('.' + cssClass);
    if (!elements.length || !images.length) return;

    elements.forEach(el => {
      let index = 0;
      el.style.backgroundSize = 'cover';
      el.style.backgroundPosition = 'center';
      el.style.backgroundImage = `url(${getImageUrl(images[0])})`;

      setInterval(() => {
        index = (index + 1) % images.length;
        el.style.backgroundImage = `url(${getImageUrl(images[index])})`;
      }, duration);
    });
  });

  function getImageUrl(id) {
    const img = document.querySelector(`img[data-id="${id}"]`);
    if (img) return img.src;
    return `/wp-content/uploads/${id}`;
  }
});
