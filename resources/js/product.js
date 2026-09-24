// کامپوننت‌های صفحه محصول: گالری با زوم و انتخاب‌گر مدل.

/**
 * گالری تصاویر.
 *  - دسکتاپ: با رفتن ماوس روی هر نقطه تصویر، همان‌جا زوم می‌شود.
 *  - کلیک/لمس: نمایش تمام‌صفحه؛ آنجا با کلیک زوم و با حرکت ماوس/انگشت جابه‌جایی.
 *  - زوایای مختلف: فلش‌ها، بندانگشتی‌ها، کیبورد و سوایپ.
 * (صفحه راست‌به‌چپ است: «بعدی» به سمت چپ می‌رود.)
 */
export const productGallery = (images = []) => ({
    images,
    index: 0,
    zoom: false,
    ox: 50,
    oy: 50,
    lightbox: false,
    lbZoom: false,
    lbX: 50,
    lbY: 50,
    touchX: null,
    swiped: false,

    get current() {
        return this.images[this.index];
    },

    // با تغییر مدل، گالری به تصاویر همان مدل (یا عمومی محصول) عوض می‌شود
    setImages(list) {
        const same = JSON.stringify(list) === JSON.stringify(this.images);
        if (same) return;
        this.images = list;
        this.index = 0;
        this.zoom = false;
        this.lbZoom = false;
    },

    go(i) {
        if (!this.images.length) return;
        this.index = (i + this.images.length) % this.images.length;
        this.zoom = false;
        this.lbZoom = false;
    },
    next() { this.go(this.index + 1); },
    prev() { this.go(this.index - 1); },

    point(e, el) {
        const r = el.getBoundingClientRect();
        const cx = e.touches ? e.touches[0].clientX : e.clientX;
        const cy = e.touches ? e.touches[0].clientY : e.clientY;
        return [
            Math.min(100, Math.max(0, ((cx - r.left) / r.width) * 100)),
            Math.min(100, Math.max(0, ((cy - r.top) / r.height) * 100)),
        ];
    },

    hoverMove(e) {
        // فقط دستگاه‌هایی که ماوس واقعی دارند؛ روی لمسی تصویر با هر تاچ زوم نمی‌شود
        if (!window.matchMedia('(hover: hover)').matches) return;
        [this.ox, this.oy] = this.point(e, e.currentTarget);
        this.zoom = true;
    },
    hoverLeave() { this.zoom = false; },

    open() {
        if (this.swiped) { this.swiped = false; return; }
        if (!this.images.length) return;
        this.lightbox = true;
        this.lbZoom = false;
        document.documentElement.classList.add('overflow-hidden');
    },
    close() {
        this.lightbox = false;
        this.lbZoom = false;
        document.documentElement.classList.remove('overflow-hidden');
    },

    lbToggle(e) {
        if (this.lbZoom) { this.lbZoom = false; return; }
        [this.lbX, this.lbY] = this.point(e, e.currentTarget);
        this.lbZoom = true;
    },
    lbMove(e) {
        if (!this.lbZoom) return;
        [this.lbX, this.lbY] = this.point(e, e.currentTarget);
    },

    touchStart(e) {
        this.touchX = e.touches[0].clientX;
        this.swiped = false;
    },
    touchEnd(e, inLightbox = false) {
        if (this.touchX === null || (inLightbox && this.lbZoom)) { this.touchX = null; return; }
        const dx = e.changedTouches[0].clientX - this.touchX;
        this.touchX = null;
        if (Math.abs(dx) > 50) {
            this.swiped = true;
            dx < 0 ? this.next() : this.prev();
        }
    },
});

/**
 * انتخاب‌گر مدل محصول. قیمت و موجودی هر مدل از سرور (PriceResolver) آمده؛
 * اینجا فقط بین آن‌ها جابه‌جا می‌شویم و هیچ قیمتی محاسبه نمی‌کنیم.
 */
export const productBuy = (cfg = {}) => ({
    variants: cfg.variants || [],
    groups: cfg.groups || [],
    variantId: cfg.initial ?? null,
    general: cfg.general || [],
    selected: {},
    qty: 1,

    init() {
        if (this.current) this.selected = { ...this.current.options };
    },

    get hasVariants() { return this.variants.length > 0; },
    get current() { return this.variants.find((v) => v.id === this.variantId) || null; },
    get canBuy() {
        return !this.hasVariants || (this.current && this.current.available && !this.current.hidden && !this.current.callFor);
    },

    pick(key, val) {
        const wanted = { ...this.selected, [key]: val };
        const exact = this.variants.find((v) => this.groups.every((g) => v.options[g.key] === wanted[g.key]));
        // ترکیب دقیق وجود ندارد؛ نزدیک‌ترین مدلِ دارای همین مقدار (ترجیحاً موجود)
        const near = this.variants
            .filter((v) => v.options[key] === val)
            .sort((a, b) => Number(b.available) - Number(a.available))[0];
        this.choose((exact || near || {}).id);
    },

    choose(id) {
        if (id === undefined || id === null) return;
        this.variantId = id;
        this.selected = { ...(this.current?.options || {}) };
        this.clampQty();
        const own = this.current?.images || [];
        window.dispatchEvent(new CustomEvent('variant-images', { detail: own.length ? own : this.general }));
    },

    valueAvailable(key, val) {
        return this.variants.some((v) => v.options[key] === val && v.available);
    },

    clampQty() {
        const m = this.current?.max;
        if (m !== null && m !== undefined) this.qty = Math.min(this.qty, Math.max(1, m));
    },
    inc() {
        const m = this.current?.max;
        if (m === null || m === undefined || this.qty < m) this.qty++;
    },
    dec() { this.qty = Math.max(1, this.qty - 1); },
});
