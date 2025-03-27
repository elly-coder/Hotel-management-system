document.addEventListener('DOMContentLoaded', function() {
    // Mobile Menu Toggle
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const mainNav = document.getElementById('main-nav');
    
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', function() {
            const navList = mainNav.querySelector('ul');
            navList.classList.toggle('active');
            
            // Toggle to X
            const spans = this.querySelectorAll('span');
            if (navList.classList.contains('active')) {
                spans[0].style.transform = 'rotate(45deg) translate(5px, 5px)';
                spans[1].style.opacity = '0';
                spans[2].style.transform = 'rotate(-45deg) translate(7px, -6px)';
            } else {
                spans[0].style.transform = 'none';
                spans[1].style.opacity = '1';
                spans[2].style.transform = 'none';
            }
        });
    }
    
    // Reservation Form Submission
    const reservationForm = document.getElementById('reservation-form');
    
    if (reservationForm) {
        reservationForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const date = document.getElementById('date').value;
            const time = document.getElementById('time').value;
            const adult = document.getElementById('adult').value;
            const child = document.getElementById('child').value;
            
            if (!date || !time || !adult) {
                alert('Please fill in all required fields');
                return;
            }
            
            // Simulate form submission
            alert(`Reservation submitted!\nDate: ${date}\nTime: ${time}\nAdults: ${adult}\nChildren: ${child}`);
            
            
        });
    }
    
    // Book Now Button Click Events
    const bookNowButtons = document.querySelectorAll('.btn-dark');
    
    bookNowButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const tableCard = this.closest('.table-card');
            const tableName = tableCard.querySelector('h3').textContent;
            const tableStatus = tableCard.querySelector('.status').textContent;
            
            if (tableStatus === 'Maintenance') {
                alert(`Sorry, ${tableName} is currently under maintenance and not available for booking.`);
                return;
            }
            
            if (tableStatus === 'Occupied') {
                alert(`Sorry, ${tableName} is currently occupied. Please check back later or choose another table.`);
                return;
            }
            
            if (tableStatus === 'Reserved') {
                alert(`Sorry, ${tableName} is already reserved for today. Please select another table or try a different date.`);
                return;
            }
            
            // For available tables, proceed with booking
            alert(`You're about to book ${tableName}. Please fill in the reservation form at the top of the page to complete your booking.`);
            
            // Scroll to reservation form
            document.getElementById('reservation-form').scrollIntoView({ behavior: 'smooth' });
            
            
        });
    });
    
    // View More Button Click Events
    const viewMoreButtons = document.querySelectorAll('.btn-outline');
    
    viewMoreButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const tableCard = this.closest('.table-card');
            const tableName = tableCard.querySelector('h3').textContent;
            const tableDescription = tableCard.querySelector('p').textContent;
            const tableCapacity = tableCard.querySelector('.details').textContent;
            
            // Create a modal to display more information
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.innerHTML = `
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h2>${tableName}</h2>
                    <p>${tableDescription}</p>
                    <p>${tableCapacity}</p>
                    <div class="modal-image">
                        <img src="${tableCard.querySelector('img').src}" alt="${tableName}">
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Add modal styles dynamically
            const style = document.createElement('style');
            style.textContent = `
                .modal {
                    display: block;
                    position: fixed;
                    z-index: 1000;
                    left: 0;
                    top: 0;
                    width: 100%;
                    height: 100%;
                    background-color: rgba(0,0,0,0.7);
                }
                .modal-content {
                    background-color: white;
                    margin: 10% auto;
                    padding: 20px;
                    border-radius: 8px;
                    width: 80%;
                    max-width: 600px;
                }
                .close {
                    color: #aaa;
                    float: right;
                    font-size: 28px;
                    font-weight: bold;
                    cursor: pointer;
                }
                .close:hover {
                    color: #000;
                }
                .modal-image {
                    margin-top: 20px;
                }
                .modal-image img {
                    width: 100%;
                    border-radius: 8px;
                }
            `;
            document.head.appendChild(style);
            
            // Close modal when clicking the X
            modal.querySelector('.close').addEventListener('click', function() {
                document.body.removeChild(modal);
                document.head.removeChild(style);
            });
            
            // Close modal when clicking outside the content
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    document.body.removeChild(modal);
                    document.head.removeChild(style);
                }
            });
        });
    });
    
    // Update table status dynamically (for demo purposes)
    function updateTableStatus() {
        const statuses = ['Available', 'Occupied', 'Reserved', 'Maintenance'];
        const statusElements = document.querySelectorAll('.status');
        
        statusElements.forEach(element => {
            // Only update some tables randomly to simulate real-world changes
            if (Math.random() > 0.7) {
                const randomStatus = statuses[Math.floor(Math.random() * statuses.length)];
                
                // Remove all status classes
                element.classList.remove('available', 'occupied', 'reserved', 'maintenance');
                
                // Add new status class and text
                element.textContent = randomStatus;
                element.classList.add(randomStatus.toLowerCase());
            }
        });
    }
    
    // Update status every 30 seconds for demo purposes
    // setInterval(updateTableStatus, 30000);
});