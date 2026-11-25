/**
 * HERITAGE COASTAL VILLA - LIVE PRICE CALCULATOR (assets/js/calculator.js)
 * Updates the Reservation Summary on book.php instantly (no page reload).
 * NOTE: This is display only. The server (process_booking.php) always recalculates the real total.
 * Pricing rule (same as the server): suite total = nightly rate x nights, minus the long-stay discount for the number of nights,
 * plus each ticked add-on once.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var roomSelect = document.getElementById('room_select');
        var checkIn    = document.getElementById('check_in');
        var checkOut   = document.getElementById('check_out');

        // This script is loaded on every page: stop quietly if the booking form is not here
        if (!roomSelect || !checkIn || !checkOut) { return; }

        var CURRENCY = window.CURRENCY_SYMBOL || '$';
        var LKR      = window.LKR_SYMBOL || 'LKR ';
        var RATE     = parseFloat(window.USD_TO_LKR) || 300;
        var TIERS    = window.DISCOUNT_TIERS || []; // [[minNights, percent], ...] highest first

        var out = {
            rate:     document.getElementById('calc_room_rate'),
            nights:   document.getElementById('calc_nights'),
            subtotal: document.getElementById('calc_room_subtotal'),
            discount: document.getElementById('calc_discount'),
            discountLabel: document.getElementById('calc_discount_label'),
            addons:   document.getElementById('calc_addons_subtotal'),
            total:    document.getElementById('calc_grand_total'),
            hidden:   document.getElementById('hidden_grand_total')
        };

        // Shows both currencies, e.g. "$120.00 (LKR 36,000)"
        function money(n) {
            var usd = CURRENCY + n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            var lkr = LKR + Math.round(n * RATE).toLocaleString('en-US');
            return usd + ' (' + lkr + ')';
        }

        function setText(el, text) {
            if (el) { el.textContent = text; }
        }

        function discountPercent(nights) {
            for (var i = 0; i < TIERS.length; i++) {
                if (nights >= TIERS[i][0]) { return TIERS[i][1]; }
            }
            return 0;
        }

        // Whole nights between two yyyy-mm-dd values (0 if invalid or check-out <= check-in)
        function countNights() {
            if (!checkIn.value || !checkOut.value) { return 0; }
            var a = new Date(checkIn.value + 'T00:00:00');
            var b = new Date(checkOut.value + 'T00:00:00');
            if (isNaN(a) || isNaN(b)) { return 0; }
            var diff = Math.round((b - a) / 86400000);
            return diff > 0 ? diff : 0;
        }

        function calculate() {
            var selected = roomSelect.options[roomSelect.selectedIndex];
            var rate = selected ? parseFloat(selected.getAttribute('data-price')) || 0 : 0;
            var nights = countNights();

            var roomSubtotal = rate * nights;

            var addonsTotal = 0;
            var boxes = document.querySelectorAll('.addon-checkbox');
            for (var i = 0; i < boxes.length; i++) {
                if (boxes[i].checked) {
                    addonsTotal += parseFloat(boxes[i].getAttribute('data-price')) || 0;
                }
            }

            var pct = discountPercent(nights);
            var discount = Math.round(roomSubtotal * pct) / 100;   // same rounding as the server (2 decimals)
            var grand = roomSubtotal - discount + addonsTotal;

            setText(out.rate, money(rate));
            setText(out.nights, nights > 0 ? nights + (nights === 1 ? ' Night' : ' Nights') : 'Select dates');
            setText(out.subtotal, money(roomSubtotal));
            setText(out.discount, discount > 0 ? '-' + money(discount) : money(0));
            setText(out.discountLabel, pct > 0 ? 'Long-stay Discount (' + pct + '% off):' : 'Long-stay Discount:');
            setText(out.addons, money(addonsTotal));
            setText(out.total, money(grand));
            if (out.hidden) { out.hidden.value = grand.toFixed(2); }
        }

        // Keep check-out at least one day after check-in
        function syncCheckOutMin() {
            if (!checkIn.value) { return; }
            var d = new Date(checkIn.value + 'T00:00:00');
            if (isNaN(d)) { return; }
            d.setDate(d.getDate() + 1);
            var min = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
            checkOut.min = min;
            if (checkOut.value && checkOut.value < min) { checkOut.value = min; }
        }

        checkIn.addEventListener('change', function () { syncCheckOutMin(); calculate(); });
        checkIn.addEventListener('input',  function () { syncCheckOutMin(); calculate(); });
        checkOut.addEventListener('change', calculate);
        checkOut.addEventListener('input', calculate);
        roomSelect.addEventListener('change', calculate);

        var guests = document.getElementById('num_guests');
        if (guests) { guests.addEventListener('change', calculate); }

        var boxes = document.querySelectorAll('.addon-checkbox');
        for (var i = 0; i < boxes.length; i++) {
            boxes[i].addEventListener('change', calculate);
        }

        // Initial calculation (works with pre-filled dates / ticked add-ons)
        syncCheckOutMin();
        calculate();
    });
})();