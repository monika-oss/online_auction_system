document.addEventListener('DOMContentLoaded', function () {
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;

            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                window.scrollTo({
                    top: targetElement.offsetTop - 80,
                    behavior: 'smooth'
                });
            }
        });
    });

    // Add hover effects to category items
    const categoryItems = document.querySelectorAll('.category-item');
    categoryItems.forEach(item => {
        item.addEventListener('mouseenter', () => {
            item.style.transform = 'scale(1.05)';
            item.style.boxShadow = '0 8px 16px rgba(0, 0, 0, 0.2)';
        });

        item.addEventListener('mouseleave', () => {
            item.style.transform = 'scale(1)';
            item.style.boxShadow = '0 4px 8px rgba(0, 0, 0, 0.1)';
        });
    });

    // Dynamic category filtering
    function filterCategory(category) {
        categoryItems.forEach(item => {
            const itemCategory = item.querySelector('p').textContent;
            if (category === 'all' || itemCategory === category) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });

        // Show a temporary message about the filter
        const filterMessage = document.createElement('div');
        filterMessage.textContent = 'Showing: ${category}';
        filterMessage.style.position = 'fixed';
        filterMessage.style.bottom = '20px';
        filterMessage.style.left = '50%';
        filterMessage.style.transform = 'translateX(-50%)';
        filterMessage.style.background = '#333';
        filterMessage.style.color = '#fff';
        filterMessage.style.padding = '10px 20px';
        filterMessage.style.borderRadius = '30px';
        filterMessage.style.zIndex = '1000';
        filterMessage.style.boxShadow = '0 2px 10px rgba(0, 0, 0, 0.2)';
        document.body.appendChild(filterMessage);

        setTimeout(() => {
            filterMessage.style.animation = 'fadeOut 0.3s ease';
            setTimeout(() => {
                document.body.removeChild(filterMessage);
            }, 300);
        }, 2000);
    }

    // Add click event listeners to category items
    categoryItems.forEach(item => {
        item.addEventListener('click', () => {
            const category = item.querySelector('p').textContent;
            filterCategory(category);
        });
    });

    // Add hover effects to buttons
    const buttons = document.querySelectorAll('button');
    buttons.forEach(button => {
        button.addEventListener('mouseenter', () => {
            button.style.transform = 'translateY(-2px)';
            button.style.boxShadow = '0 4px 8px rgba(0, 0, 0, 0.2)';
        });

        button.addEventListener('mouseleave', () => {
            button.style.transform = 'translateY(0)';
            button.style.boxShadow = 'none';
        });
    });

    // Hero section button click handler
    const heroButton = document.querySelector('.hero button');
    if (heroButton) {
        heroButton.addEventListener('click', () => {
            alert('Start bidding now!');
        });
    }

   
});