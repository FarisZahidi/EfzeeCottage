<script>
    // DOM Elements
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const navLinks = document.querySelector('.nav-links');
    const sections = document.querySelectorAll('.parallax-section');
    const navDots = document.querySelectorAll('.active-section a');
    const loginModal = document.getElementById('loginModal');
    const loginBtn = document.getElementById('loginBtn');
    const closeBtn = document.querySelector('.close');
    const tabBtns = document.querySelectorAll('.tab-btn');
    const bookingForm = document.getElementById('bookingForm');
    const homestaySelect = document.getElementById('homestaySelect');
    const checkInDate = document.getElementById('checkInDate');
    const checkOutDate = document.getElementById('checkOutDate');

    // Initialize
    document.addEventListener('DOMContentLoaded', function () {
        initMobileMenu();
        initNavbarScroll();
        initActiveSection();
        initModal();
        initBooking();
        initDatePickers();

        // Set initial dates
        const today = new Date();
        checkInDate.valueAsDate = today;
        checkOutDate.valueAsDate = new Date(today.getTime() + 24 * 60 * 60 * 1000);
    });

    // Functions
    function initMobileMenu() {
        mobileMenuBtn.addEventListener('click', () => {
            navLinks.classList.toggle('active');
            mobileMenuBtn.innerHTML = navLinks.classList.contains('active') ?
                '<i class="fas fa-times"></i>' : '<i class="fas fa-bars"></i>';
        });
    }

    function initNavbarScroll() {
        window.addEventListener('scroll', () => {
            document.querySelector('.navbar').classList.toggle('scrolled', window.scrollY > 50);
        });
    }

    function initActiveSection() {
        window.addEventListener('scroll', () => {
            let current = '';

            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                const sectionHeight = section.clientHeight;

                if (pageYOffset >= (sectionTop - sectionHeight / 3)) {
                    current = section.getAttribute('id');
                }
            });

            navDots.forEach(dot => {
                dot.classList.toggle('active', dot.getAttribute('href') === `#${current}`);
            });

            document.querySelectorAll('.nav-links a').forEach(link => {
                link.classList.toggle('active', link.getAttribute('href') === `#${current}`);
            });
        });
    }

    function initModal() {
        // Modal toggle
        loginBtn?.addEventListener('click', () => toggleModal(true));
        closeBtn?.addEventListener('click', () => toggleModal(false));
        window.addEventListener('click', (e) => e.target === loginModal && toggleModal(false));

        // Tab switching
        tabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelector('.tab-btn.active')?.classList.remove('active');
                btn.classList.add('active');

                const tab = btn.getAttribute('data-tab');
                document.querySelectorAll('.auth-form').forEach(form => {
                    form.style.display = form.id === `${tab}Form` ? 'flex' : 'none';
                });
            });
        });
    }

    function toggleModal(show) {
        loginModal.style.display = show ? 'flex' : 'none';
        document.body.style.overflow = show ? 'hidden' : 'auto';
    }

    function initBooking() {
        if (!bookingForm) return;

        // Event listeners
        [checkInDate, checkOutDate, homestaySelect, document.getElementById('totalGuests')].forEach(el => {
            el?.addEventListener('change', calculateTotal);
        });

        document.querySelectorAll('input[name="amenities[]"]').forEach(checkbox => {
            checkbox.addEventListener('change', calculateTotal);
        });

        bookingForm.addEventListener('submit', submitBooking);
        homestaySelect.addEventListener('change', fetchAmenities);

        // Initial calculation
        calculateTotal();
    }

    async function calculateTotal() {
        const selectedOption = homestaySelect.options[homestaySelect.selectedIndex];
        const pricePerNight = parseFloat(selectedOption.getAttribute('data-price'));
        const maxGuests = parseInt(selectedOption.getAttribute('data-max-guests'));
        const guests = parseInt(document.getElementById('totalGuests').value) || 1;

        // Validate guests
        if (guests > maxGuests) {
            document.getElementById('totalGuests').value = maxGuests;
            showAlert('Maximum Guests Exceeded', `This homestay can only accommodate ${maxGuests} guests.`, 'warning');
        }

        // Calculate nights
        const checkIn = new Date(checkInDate.value);
        const checkOut = new Date(checkOutDate.value);
        const nights = checkIn && checkOut && checkOut > checkIn ?
            Math.ceil((checkOut - checkIn) / (1000 * 60 * 60 * 24)) : 0;

        // Calculate base rate
        const baseRate = pricePerNight * nights;

        // Calculate amenities
        let amenitiesTotal = 0;
        const selectedAmenities = [];

        document.querySelectorAll('input[name="amenities[]"]:checked').forEach(checkbox => {
            const amenityId = parseInt(checkbox.value);
            const amenity = JSON.parse(checkbox.dataset.amenity);
            amenitiesTotal += amenity.price;
            selectedAmenities.push(amenity);
        });

        // Calculate subtotal
        const subtotal = (baseRate * nights) + (amenitiesTotal * nights);

        // Get discount if logged in
        let discountAmount = 0;
        if (<?php echo isset($_SESSION['user']) ? 'true' : 'false'; ?>) {
            try {
                const response = await fetch(`get_loyalty_discount.php?user_id=<?= $_SESSION['user']['user_id'] ?? 0 ?>`);
                const data = await response.json();
                if (data.tier_name === 'Returning Customer') {
                    discountAmount = 20.00;
                }
            } catch (error) {
                console.error('Error fetching discount:', error);
            }
        }

        // Update UI
        updatePriceBreakdown(baseRate, nights, selectedAmenities, subtotal, discountAmount);
        document.getElementById('calculatedPrice').value = (subtotal - discountAmount).toFixed(2);
    }

    function updatePriceBreakdown(baseRate, nights, amenities, subtotal, discount = 0) {
        document.getElementById('baseRate').textContent = `RM ${(baseRate / nights).toFixed(2)}`;
        document.getElementById('numberOfNights').textContent = nights;
        document.getElementById('subtotal').textContent = `RM ${subtotal.toFixed(2)}`;
        document.getElementById('totalPrice').textContent = `RM ${(subtotal - discount).toFixed(2)}`;
        document.getElementById('totalPriceInput').value = (subtotal - discount).toFixed(2);

        // Update amenities display
        const container = document.getElementById('amenityChargesContainer');
        container.innerHTML = amenities.map(a => `
        <div class="price-row">
            <span><i class="${a.icon}"></i> ${a.name}:</span>
            <span>RM ${a.price.toFixed(2)}</span>
        </div>
    `).join('');

        // Update discount display
        const discountEl = document.querySelector('.discount-row');
        discountEl.style.display = 'flex';
        const discountLabel = document.querySelector('.discount-row span:first-child');
        discountLabel.textContent = 'Loyalty Discount (RM20):';
        document.getElementById('discountAmount').textContent = discount > 0 ? `-RM 20.00` : `-RM 0.00`;
        document.getElementById('totalPrice').textContent = `RM ${(subtotal - discount).toFixed(2)}`;
        document.getElementById('totalPriceInput').value = (subtotal - discount).toFixed(2);
    }

    async function fetchAmenities() {
        const homestayId = homestaySelect.value;
        if (!homestayId) return;

        try {
            const response = await fetch(`get_amenities.php?homestay_id=${homestayId}`);
            const data = await response.json();

            const container = document.querySelector('.amenities-grid');
            container.innerHTML = data.map(amenity => `
            <div class="amenity-item">
                <input type="checkbox" name="amenities[]" id="amenity${amenity.amenity_id}" 
                       value="${amenity.amenity_id}" data-amenity='${JSON.stringify(amenity)}'>
                <label for="amenity${amenity.amenity_id}">
                    <i class="${amenity.icon}"></i>
                    <span>${amenity.name} (RM${parseFloat(amenity.price).toFixed(2)})</span>
                </label>
            </div>
        `).join('');

            // Add event listeners to new checkboxes
            document.querySelectorAll('input[name="amenities[]"]').forEach(checkbox => {
                checkbox.addEventListener('change', calculateTotal);
            });
        } catch (error) {
            console.error('Error fetching amenities:', error);
        }
    }

    function initDatePickers() {
        checkInDate.addEventListener('change', function () {
            if (this.value) {
                const nextDay = new Date(this.value);
                nextDay.setDate(nextDay.getDate() + 1);
                checkOutDate.min = nextDay.toISOString().split('T')[0];

                if (checkOutDate.value && new Date(checkOutDate.value) <= new Date(this.value)) {
                    checkOutDate.value = '';
                }
            }
            calculateTotal();
        });

        checkOutDate.addEventListener('change', calculateTotal);
    }

    async function submitBooking(e) {
        e.preventDefault();
        const formData = new FormData(bookingForm);

        try {
            const response = await fetch("process_booking.php", {
                method: "POST",
                body: formData
            });
            const data = await response.json();

            if (data.success) {
                showAlert('Booking Successful!', 'Your booking has been placed successfully.', 'success')
                    .then(() => window.location.href = 'mybooking.php');
            } else {
                showAlert('Booking Failed', data.message || 'An error occurred. Please try again.', 'error');
            }
        } catch (error) {
            console.error("Error:", error);
            showAlert('System Error', 'Something went wrong!', 'error');
        }
    }

    function showAlert(title, text, icon) {
        return Swal.fire({
            title,
            text,
            icon,
            confirmButtonColor: getComputedStyle(document.documentElement)
                .getPropertyValue('--secondary-color').trim()
        });
    }
</script>