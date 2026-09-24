import './bootstrap';
import './push';

import Alpine from 'alpinejs';
import { productGallery, productBuy } from './product';

window.Alpine = Alpine;
Alpine.data('productGallery', productGallery);
Alpine.data('productBuy', productBuy);
Alpine.start();
