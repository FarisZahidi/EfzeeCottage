document.addEventListener('DOMContentLoaded', function() {
    // Initialize FullCalendar
    var calendarEl = document.getElementById('bookingCalendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,listWeek'
        },
        selectable: true,
        editable: false,
        eventClick: function(info) {
            showBookingDetails(info.event.id);
        },
        dateClick: function(info) {
            showDateBookings(info.dateStr);
        },
        events: function(info, successCallback, failureCallback) {
            // Fetch events from server
            fetch('get_calendar_events.php?start=' + info.startStr + '&end=' + info.endStr)
                .then(response => response.json())
                .then(data => {
                    // Transform bookings into calendar events
                    const events = data.map(booking => ({
                        id: booking.booking_id,
                        title: `${booking.homestay_name} - ${booking.guest_name}`,
                        start: booking.check_in_date,
                        end: booking.check_out_date,
                        backgroundColor: getStatusColor(booking.status),
                        borderColor: getStatusColor(booking.status),
                        extendedProps: {
                            status: booking.status,
                            payment_status: booking.payment_status
                        }
                    }));
                    successCallback(events);
                })
                .catch(error => {
                    console.error('Error fetching calendar events:', error);
                    failureCallback(error);
                });
        }
    });

    calendar.render();

    // Function to show booking details modal
    function showBookingDetails(bookingId) {
        fetch('get_booking_details.php?id=' + bookingId)
            .then(response => response.text())
            .then(html => {
                document.getElementById('bookingDetailsContent').innerHTML = html;
                new bootstrap.Modal(document.getElementById('bookingDetailsModal')).show();
            })
            .catch(error => console.error('Error fetching booking details:', error));
    }

    // Function to show bookings for a specific date
    function showDateBookings(date) {
        fetch('get_date_bookings.php?date=' + date)
            .then(response => response.text())
            .then(html => {
                document.getElementById('dateBookingsContent').innerHTML = html;
                new bootstrap.Modal(document.getElementById('dateBookingsModal')).show();
            })
            .catch(error => console.error('Error fetching date bookings:', error));
    }

    // Function to get color based on booking status
    function getStatusColor(status) {
        switch (status) {
            case 'confirmed':
                return '#28a745'; // Green
            case 'pending':
                return '#ffc107'; // Yellow
            case 'cancelled':
                return '#dc3545'; // Red
            default:
                return '#6c757d'; // Gray
        }
    }

    // Handle booking status updates
    window.updateBookingStatus = function(bookingId, status) {
        fetch('update_booking_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `booking_id=${bookingId}&status=${status}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Refresh calendar events
                calendar.refetchEvents();
                // Show success message
                showAlert('success', 'Booking status updated successfully');
            } else {
                showAlert('danger', 'Failed to update booking status: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error updating booking status:', error);
            showAlert('danger', 'Error updating booking status');
        });
    };

    // Handle payment status updates
    window.updatePaymentStatus = function(paymentId, status) {
        fetch('update_payment_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `payment_id=${paymentId}&status=${status}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Refresh calendar events
                calendar.refetchEvents();
                // Show success message
                showAlert('success', 'Payment status updated successfully');
            } else {
                showAlert('danger', 'Failed to update payment status: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error updating payment status:', error);
            showAlert('danger', 'Error updating payment status');
        });
    };

    // Function to show alert messages
    function showAlert(type, message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        document.getElementById('alertContainer').appendChild(alertDiv);

        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }
});