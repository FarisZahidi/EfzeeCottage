<?php
require_once 'include/config.php';
require_once 'include/login_signup.php';

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EFZEE COTTAGE - Luxury Retreat in Batu Pahat</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;600&display=swap"
        rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <!-- Navigation Bar -->
    <?php include 'components/navbar.php'; ?>

    <!-- Home Section -->
    <?php include 'templates/home.php'; ?>

    <!-- About Section -->
    <?php include 'templates/about.php'; ?>

    <!-- Gallery Section -->
    <?php include 'templates/gallery.php'; ?>

    <!-- Booking Section -->
    <?php include 'templates/booking.php'; ?>

    <!-- Reviews Section -->
    <!-- <?php include 'templates/reviews.php'; ?> -->

    <!-- Footer -->
    <?php include 'components/footer.php'; ?>

    <!-- Login Modal -->
    <?php include 'components/login_modal.php'; ?>



    <!-- All Scripts Here -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Mobile Version -->

    <script>
        // Mobile Menu Toggle
        const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
        const navLinks = document.querySelector('.nav-links');

        mobileMenuBtn.addEventListener('click', () => {
            navLinks.classList.toggle('active');
            mobileMenuBtn.innerHTML = navLinks.classList.contains('active') ?
                '<i class="fas fa-times"></i>' : '<i class="fas fa-bars"></i>';
        });

        // Navbar Scroll Effect
        window.addEventListener('scroll', () => {
            const navbar = document.querySelector('.navbar');
            navbar.classList.toggle('scrolled', window.scrollY > 50);
        });

        // Active Section Indicator
        const sections = document.querySelectorAll('.parallax-section');
        const navDots = document.querySelectorAll('.active-section a');

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
                dot.classList.remove('active');
                if (dot.getAttribute('href') === `#${current}`) {
                    dot.classList.add('active');
                }
            });

            // Update nav links
            document.querySelectorAll('.nav-links a').forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === `#${current}`) {
                    link.classList.add('active');
                }
            });
        });

        // Modal Functionality
        const loginModal = document.getElementById('loginModal');
        const loginBtn = document.getElementById('loginBtn');
        const closeBtn = document.querySelector('.close');
        const tabBtns = document.querySelectorAll('.tab-btn');

        loginBtn.addEventListener('click', () => {
            loginModal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        });

        closeBtn.addEventListener('click', () => {
            loginModal.style.display = 'none';
            document.body.style.overflow = 'auto';
        });

        window.addEventListener('click', (e) => {
            if (e.target === loginModal) {
                loginModal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });

        tabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                // Switch tabs
                document.querySelector('.tab-btn.active').classList.remove('active');
                btn.classList.add('active');

                // Show corresponding form
                const tab = btn.getAttribute('data-tab');
                document.querySelectorAll('.auth-form').forEach(form => {
                    form.style.display = 'none';
                });
                document.getElementById(`${tab}Form`).style.display = 'flex';
            });
        });

        // Check login status on page load
        function checkLoginStatus() {
            const isLoggedIn = <?php echo isset($_SESSION['user']) ? 'true' : 'false'; ?>;

            if (isLoggedIn) {
                document.getElementById('loginBtn').style.display = 'none';
                document.getElementById('logoutBtn').style.display = 'block';
                document.getElementById('bookingForm').style.display = 'block';
                document.getElementById('loginMessage').style.display = 'none';
            } else {
                document.getElementById('loginBtn').style.display = 'block';
                document.getElementById('logoutBtn').style.display = 'none';
                document.getElementById('bookingForm').style.display = 'none';
                document.getElementById('loginMessage').style.display = 'block';
            }
        }

        // Initialize on page load
        checkLoginStatus();
    </script>

    <!-- Booking Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Initialize elements
            const bookingForm = document.getElementById('bookingForm');
            const checkInDate = document.getElementById('checkInDate');
            const checkOutDate = document.getElementById('checkOutDate');
            const homestaySelect = document.getElementById('homestaySelect');
            const totalGuests = document.getElementById('totalGuests');
            const baseRateElement = document.getElementById('baseRate');
            const numberOfNightsElement = document.getElementById('numberOfNights');
            const subtotalElement = document.getElementById('subtotal');
            const totalPriceElement = document.getElementById('totalPrice');
            const calculatedPriceInput = document.getElementById('calculatedPrice');
            const amenitiesContainer = document.getElementById('amenitiesContainer');
            const selectedAmenitiesContainer = document.getElementById('selectedAmenitiesContainer');

            // Load amenities for selected homestay
            async function loadAmenities(homestayId) {
                try {
                    const response = await fetch(`get_amenities.php?homestay_id=${homestayId}`);
                    const amenities = await response.json();

                    // Clear and rebuild amenities container
                    amenitiesContainer.innerHTML = '<h4>Additional Amenities:</h4>';
                    amenities.forEach(amenity => {
                        const amenityDiv = document.createElement('div');
                        amenityDiv.className = 'amenity-option';
                        amenityDiv.innerHTML = `
                            <label>
                                <input type="checkbox" name="amenities[]" value="${amenity.amenity_id}" 
                                       data-price="${amenity.price}" data-name="${amenity.name}" 
                                       data-icon="${amenity.icon}">
                                <i class="${amenity.icon}"></i> ${amenity.name} - RM${amenity.price.toFixed(2)}
                            </label>
                        `;
                        amenitiesContainer.appendChild(amenityDiv);
                    });

                    // Add change listeners to new checkboxes
                    document.querySelectorAll('input[name="amenities[]"]').forEach(checkbox => {
                        checkbox.addEventListener('change', calculateTotal);
                    });
                } catch (error) {
                    console.error('Error loading amenities:', error);
                }
            }

            // Calculate total price
            async function calculateTotal() {
                const selectedOption = homestaySelect.options[homestaySelect.selectedIndex];
                if (!selectedOption || !checkInDate.value || !checkOutDate.value) return;

                const pricePerNight = parseFloat(selectedOption.dataset.price);
                const checkIn = new Date(checkInDate.value);
                const checkOut = new Date(checkOutDate.value);
                const nights = Math.ceil((checkOut - checkIn) / (1000 * 60 * 60 * 24));

                if (nights <= 0) return;

                // Calculate base rate
                const baseRate = pricePerNight * nights;
                baseRateElement.textContent = `RM ${pricePerNight.toFixed(2)}`;
                numberOfNightsElement.textContent = nights;

                // Calculate amenities total
                let amenitiesTotal = 0;
                const selectedAmenities = [];
                document.querySelectorAll('input[name="amenities[]"]:checked').forEach(checkbox => {
                    const price = parseFloat(checkbox.dataset.price);
                    amenitiesTotal += price;
                    selectedAmenities.push({
                        name: checkbox.dataset.name,
                        price: price,
                        icon: checkbox.dataset.icon
                    });
                });

                // Calculate subtotal
                const subtotal = baseRate + (amenitiesTotal * nights);
                subtotalElement.textContent = `RM ${subtotal.toFixed(2)}`;

                // Get and apply loyalty discount
                try {
                    const response = await fetch('get_loyalty_discount.php?user_id=<?= $_SESSION['user']['user_id'] ?? 0 ?>');
                    const data = await response.json();
                    const loyaltyDiscount = data.discount || 0;
                    const discountAmount = subtotal * (loyaltyDiscount / 100);

                    // Update discount display
                    const discountRow = document.querySelector('.discount-row');
                    if (loyaltyDiscount > 0) {
                        discountRow.style.display = 'flex';
                        discountRow.querySelector('#discountText').textContent = `Loyalty Discount (${loyaltyDiscount}%)`;
                        discountRow.querySelector('#discountAmount').textContent = `-RM ${discountAmount.toFixed(2)}`;
                    } else {
                        discountRow.style.display = 'none';
                    }

                    // Calculate and update total
                    const total = subtotal - discountAmount;
                    totalPriceElement.textContent = `RM ${total.toFixed(2)}`;
                    calculatedPriceInput.value = total.toFixed(2);

                } catch (error) {
                    console.error('Error fetching loyalty discount:', error);
                }

                // Update selected amenities display
                selectedAmenitiesContainer.innerHTML = selectedAmenities.map(amenity => `
                    <div class="price-row amenity-row">
                        <span><i class="${amenity.icon}"></i> ${amenity.name}:</span>
                        <span>RM ${(amenity.price * nights).toFixed(2)}</span>
                    </div>
                `).join('');
            }

            // Event listeners
            homestaySelect.addEventListener('change', function () {
                const selectedId = this.value;
                if (selectedId) {
                    loadAmenities(selectedId);
                    calculateTotal();
                }
            });

            [checkInDate, checkOutDate, totalGuests].forEach(element => {
                element.addEventListener('change', calculateTotal);
            });

            // Date validation
            checkInDate.addEventListener('change', function () {
                if (this.value) {
                    const nextDay = new Date(this.value);
                    nextDay.setDate(nextDay.getDate() + 1);
                    checkOutDate.min = nextDay.toISOString().split('T')[0];
                    if (checkOutDate.value && new Date(checkOutDate.value) <= new Date(this.value)) {
                        checkOutDate.value = '';
                    }
                }
            });

            // Initial load
            if (homestaySelect.value) {
                loadAmenities(homestaySelect.value);
                calculateTotal();
            }
        });
    </script>
    <script>
        // Event listeners are already added within DOMContentLoaded
        // Booking form submission
        bookingForm.addEventListener('submit', (e) => {
            e.preventDefault();
            Swal.fire({
                title: 'Booking Confirmed!',
                html: `
    <p>Your reservation at EFZEE COTTAGE has been confirmed.</p>
    <p><strong>Total:</strong> ${document.getElementById('totalPrice').textContent}</p>
    `,
                icon: 'success',
                confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--secondary-color').trim()
            });
        });

        // Initialize calendar with current date
        const today = new Date();
        document.getElementById('checkInDate').valueAsDate = today;
        document.getElementById('checkOutDate').valueAsDate = new Date(today.getTime() + 24 * 60 * 60 * 1000);

        // Calendar day click handler
        document.querySelectorAll('.calendar-day.available').forEach(day => {
            day.addEventListener('click', function () {
                if (!<?php echo isset($_SESSION['user']) ? 'true' : 'false'; ?>) {
                    document.getElementById('loginMessage').style.display = 'block';
                    loginModal.style.display = 'flex';
                    document.body.style.overflow = 'hidden';
                } else {
                    // Set dates when clicked
                    const date = new Date();
                    date.setDate(parseInt(this.textContent));
                    document.getElementById('checkIn').valueAsDate = date;
                    document.getElementById('checkOut').valueAsDate = new Date(date.getTime() + 24 * 60 * 60 * 1000);
                    calculateTotal();
                }
            });
        });
    </script>


    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const checkInInput = document.querySelector('input[name="check_in_date"]');
            const checkOutInput = document.querySelector('input[name="check_out_date"]');
            const today = new Date().toISOString().split('T')[0]; // YYYY-MM-DD

            // Set min date to today
            checkInInput.min = today;
            checkOutInput.min = today;

            // Disable booked dates
            function disableBookedDates(input) {
                input.addEventListener('input', function () {
                    const selectedDate = this.value;
                });
            }

            disableBookedDates(checkInInput);
            disableBookedDates(checkOutInput);
        });
    </script>

    <!-- Reviews -->
    <script>
        // Review Form Submission
        document.getElementById('reviewForm')?.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('submit_review.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'Review Submitted!',
                            text: 'Thank you for your feedback. Your review will be visible after approval.',
                            icon: 'success',
                            confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--secondary-color').trim()
                        }).then(() => {
                            this.reset();
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: data.message || 'Failed to submit review. Please try again.',
                            icon: 'error',
                            confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--secondary-color').trim()
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error',
                        text: 'An unexpected error occurred. Please try again.',
                        icon: 'error',
                        confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--secondary-color').trim()
                    });
                });
        });
    </script>

    <!-- Booking Form Submission -->
    <script>
        document.getElementById("bookingForm").addEventListener("submit", function (e) {
            e.preventDefault(); // prevent default form submission

            const form = e.target;
            const formData = new FormData(form);

            fetch("process_booking.php", {
                method: "POST",
                body: formData
            })
                .then(response => response.json()) // expect JSON from PHP
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Booking Successful!',
                            text: 'Your booking has been placed successfully.',
                            confirmButtonText: 'View Booking'
                        }).then(() => {
                            window.location.href = 'mybooking.php'
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Booking Failed',
                            text: data.message || 'An error occurred. Please try again.'
                        });
                    }
                })
                .catch(error => {
                    console.error("Error:", error);
                    Swal.fire({
                        icon: 'error',
                        title: 'System Error',
                        text: 'Something went wrong!'
                    });
                });
        });
    </script>

    <!-- Amenities Selection -->
    <script>
        document.getElementById('homestaySelect').addEventListener('change', function () {
            const homestayId = this.value;

            fetch(`get_amenities.php?homestay_id=${homestayId}`)
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('amenitiesContainer');
                    container.innerHTML = '';

                    if (data.length > 0) {
                        const label = document.createElement('label');
                        label.innerHTML = '<strong>Select Additional Amenities:</strong>';
                        container.appendChild(label);

                        const grid = document.createElement('div');
                        grid.className = 'amenities-grid';

                        data.forEach(amenity => {
                            const item = document.createElement('div');
                            item.className = 'amenity-item';

                            item.innerHTML = `
                        <input type="checkbox" name="amenities[]" id="amenity${amenity.amenity_id}" value="${amenity.amenity_id}">
                        <label for="amenity${amenity.amenity_id}">
                            <i class="${amenity.icon}"></i>
                            <span>${amenity.name} (RM${parseFloat(amenity.price).toFixed(2)})</span>
                        </label>
                    `;
                            grid.appendChild(item);
                        });

                        container.appendChild(grid);
                    } else {
                        container.innerHTML = '<p>No amenities available for this homestay.</p>';
                    }
                });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const homestaySelect = document.getElementById('homestaySelect');
            const baseRateSpan = document.getElementById('baseRate');
            const nightsSpan = document.getElementById('numberOfNights');
            const subtotalSpan = document.getElementById('subtotal');
            const totalSpan = document.getElementById('totalPrice');
            const amenitiesContainer = document.getElementById('amenityChargesContainer');
            const discountRow = document.querySelector('.discount-row');
            const discountAmount = document.getElementById('discountAmount');

            let baseRate = 0;
            let numberOfNights = 1; // You can adjust this or get from a date picker
            let selectedAmenities = [];

            function updatePriceBreakdown() {
                let amenityTotal = selectedAmenities.reduce((sum, a) => sum + parseFloat(a.price), 0);
                let baseSubtotal = baseRate * numberOfNights;
                let subtotal = baseSubtotal + amenityTotal;

                // Update UI
                baseRateSpan.textContent = `RM ${baseRate.toFixed(2)}`;
                nightsSpan.textContent = numberOfNights;
                subtotalSpan.textContent = `RM ${subtotal.toFixed(2)}`;
                totalSpan.textContent = `RM ${subtotal.toFixed(2)}`;

                // Show selected amenities in breakdown
                // amenitiesContainer.innerHTML = '';
                selectedAmenities.forEach(a => {
                    const row = document.createElement('div');
                    row.className = 'price-row';
                    row.innerHTML = `
                <span>${a.name}:</span>
                <span>RM ${parseFloat(a.price).toFixed(2)}</span>
            `;
                    amenitiesContainer.appendChild(row);
                });
            }

            function fetchAmenities(homestayId) {
                fetch(`get_amenities.php?homestay_id=${homestayId}`)
                    .then(res => res.json())
                    .then(data => {
                        const amenitiesGrid = document.querySelector('.amenities-grid');
                        // amenitiesGrid.innerHTML = '';

                        data.forEach(amenity => {
                            const id = `amenity${amenity.amenity_id}`;
                            const checkbox = document.createElement('input');
                            checkbox.type = 'checkbox';
                            checkbox.name = 'amenities[]';
                            checkbox.id = id;
                            checkbox.value = amenity.amenity_id;
                            checkbox.dataset.price = amenity.price;
                            checkbox.dataset.name = amenity.name;

                            const label = document.createElement('label');
                            label.htmlFor = id;
                            label.innerHTML = `<i class="${amenity.icon}"></i> <span>${amenity.name} (RM${parseFloat(amenity.price).toFixed(2)})</span>`;

                            const item = document.createElement('div');
                            item.className = 'amenity-item';
                            item.appendChild(checkbox);
                            item.appendChild(label);

                            // amenitiesGrid.appendChild(item);

                            checkbox.addEventListener('change', () => {
                                if (checkbox.checked) {
                                    selectedAmenities.push({
                                        amenity_id: amenity.amenity_id,
                                        name: amenity.name,
                                        price: parseFloat(amenity.price)
                                    });
                                } else {
                                    selectedAmenities = selectedAmenities.filter(a => a.amenity_id !== amenity.amenity_id);
                                }
                                updatePriceBreakdown();
                            });
                        });
                    });
            }

            homestaySelect.addEventListener('change', function () {
                const selected = homestaySelect.selectedOptions[0];
                baseRate = parseFloat(selected.dataset.price);
                selectedAmenities = []; // Reset
                fetchAmenities(selected.value);
                updatePriceBreakdown();
            });

            // Trigger initial if already selected
            if (homestaySelect.value) {
                const selected = homestaySelect.selectedOptions[0];
                baseRate = parseFloat(selected.dataset.price);
                fetchAmenities(selected.value);
                updatePriceBreakdown();
            }
        });
    </script>
</body>

</html>

<?php
if (isset($_SESSION['login_success'])) {
    echo "<script>
    Swal.fire({
        title: 'Login Successful!',
        text: " . json_encode($_SESSION['login_success']) . ",
        icon: 'success',
        confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--secondary-color').trim()
    });
    </script>";
    unset($_SESSION['login_success']);
}

if (isset($_SESSION['login_error'])) {
    echo "<script>
    Swal.fire({
        title: 'Login Failed',
        text: " . json_encode($_SESSION['login_error']) . ",
        icon: 'error',
        confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--secondary-color').trim()
    });
    </script>";
    unset($_SESSION['login_error']);
}
?>

<?php
if (isset($_SESSION['signup_success'])) {
    echo "<script>
    Swal.fire({
        title: 'Account Created!',
        text: " . json_encode($_SESSION['signup_success']) . ",
        icon: 'success',
        confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--secondary-color').trim()
    });
    </script>";
    unset($_SESSION['signup_success']);
}

if (isset($_SESSION['signup_error'])) {
    echo "<script>
    Swal.fire({
        title: 'Signup Failed',
        text: " . json_encode($_SESSION['signup_error']) . ",
        icon: 'error',
        confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--secondary-color').trim()
    });
    </script>";
    unset($_SESSION['signup_error']);
}
?>

<?php
// Function to get customer loyalty status
function getCustomerLoyaltyStatus($user_id, $conn)
{
    $stmt = $conn->prepare("SELECT * FROM customer_loyalty WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Function to get loyalty tiers
function getLoyaltyTiers($conn)
{
    $result = $conn->query("SELECT * FROM loyalty_tiers ORDER BY min_points ASC");
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to calculate loyalty discount
function calculateLoyaltyDiscount($user_id, $conn)
{
    $loyalty = getCustomerLoyaltyStatus($user_id, $conn);
    $tiers = getLoyaltyTiers($conn);

    if (!$loyalty)
        return 0;

    $discount = 0;
    foreach ($tiers as $tier) {
        if ($loyalty['loyalty_points'] >= $tier['min_points']) {
            $discount = $tier['discount_percentage'];
        }
    }

    return $discount;
}
?>