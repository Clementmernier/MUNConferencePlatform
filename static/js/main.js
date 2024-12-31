// Navbar scroll effect
document.addEventListener('DOMContentLoaded', function() {
    const navbar = document.querySelector('.navbar');
    
    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            navbar.classList.add('navbar-scrolled');
        } else {
            navbar.classList.remove('navbar-scrolled');
        }
    });

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Animate elements on scroll
    const animateOnScroll = () => {
        const elements = document.querySelectorAll('.animate-on-scroll');
        
        elements.forEach(element => {
            const elementPosition = element.getBoundingClientRect().top;
            const screenPosition = window.innerHeight;
            
            if (elementPosition < screenPosition) {
                element.classList.add('animated');
            }
        });
    };

    window.addEventListener('scroll', animateOnScroll);
    
    // Initialize counters animation
    const counters = document.querySelectorAll('.counter');
    const speed = 200;

    const animateCounter = () => {
        counters.forEach(counter => {
            const target = +counter.getAttribute('data-target');
            const count = +counter.innerText;
            const increment = target / speed;

            if (count < target) {
                counter.innerText = Math.ceil(count + increment);
                setTimeout(animateCounter, 1);
            } else {
                counter.innerText = target;
            }
        });
    };

    // Particle background effect
    const createParticles = () => {
        const hero = document.querySelector('.hero');
        for (let i = 0; i < 50; i++) {
            const particle = document.createElement('div');
            particle.classList.add('particle');
            particle.style.left = Math.random() * 100 + '%';
            particle.style.top = Math.random() * 100 + '%';
            particle.style.animationDelay = Math.random() * 5 + 's';
            hero.appendChild(particle);
        }
    };

    createParticles();

    // Contact form handling
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get form data
            const name = document.getElementById('name').value;
            const email = document.getElementById('email').value;
            const message = document.getElementById('message').value;
            
            // Disable submit button
            const submitButton = contactForm.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.textContent;
            submitButton.disabled = true;
            submitButton.textContent = 'Sending...';
            
            // Send email using EmailJS
            emailjs.send('default_service', 'template_contact', {
                from_name: name,
                from_email: email,
                message: message,
                to_email: 'clem.mernier@gmail.com'
            })
            .then(function(response) {
                alert('Message sent successfully!');
                contactForm.reset();
            })
            .catch(function(error) {
                console.error('Error:', error);
                alert('Failed to send message. Please try again.');
            })
            .finally(function() {
                submitButton.disabled = false;
                submitButton.textContent = originalButtonText;
            });
        });
    }
});

// Auth pages functionality
document.addEventListener('DOMContentLoaded', function() {
    // Form validation and submission
    const signupForm = document.getElementById('signupForm');
    if (signupForm) {
        signupForm.addEventListener('submit', function(e) {
            e.preventDefault();
            let isValid = true;
            const errorMessages = [];
            
            // Firstname validation
            const firstname = document.getElementById('firstname');
            if (!firstname.value.trim()) {
                showError(firstname);
                errorMessages.push("Le prénom est requis");
                isValid = false;
            } else {
                hideError(firstname);
            }

            // Lastname validation
            const lastname = document.getElementById('lastname');
            if (!lastname.value.trim()) {
                showError(lastname);
                errorMessages.push("Le nom est requis");
                isValid = false;
            } else {
                hideError(lastname);
            }
            
            // Email validation
            const email = document.getElementById('email');
            if (!email.value.trim() || !validateEmail(email.value)) {
                showError(email);
                errorMessages.push("Email invalide");
                isValid = false;
            } else {
                hideError(email);
            }
            
            // Password validation
            const password = document.getElementById('password');
            if (!password.value || password.value.length < 8) {
                showError(password);
                errorMessages.push("Le mot de passe doit contenir au moins 8 caractères");
                isValid = false;
            } else {
                hideError(password);
            }
            
            // Confirm password validation
            const confirmPassword = document.getElementById('confirmPassword');
            if (password.value !== confirmPassword.value) {
                showError(confirmPassword);
                errorMessages.push("Les mots de passe ne correspondent pas");
                isValid = false;
            } else {
                hideError(confirmPassword);
            }

            // Birthdate validation
            const birthdate = document.getElementById('birthdate');
            if (!birthdate.value) {
                showError(birthdate);
                errorMessages.push("La date de naissance est requise");
                isValid = false;
            } else {
                hideError(birthdate);
            }

            // Terms validation
            const terms = document.getElementById('terms');
            if (!terms.checked) {
                showError(terms);
                errorMessages.push("Vous devez accepter les conditions d'utilisation");
                isValid = false;
            } else {
                hideError(terms);
            }

            // Remove any existing error messages
            const existingAlert = signupForm.querySelector('.alert');
            if (existingAlert) {
                existingAlert.remove();
            }
            
            if (isValid) {
                const formData = new FormData(signupForm);
                
                // Disable submit button and show loading state
                const submitButton = signupForm.querySelector('button[type="submit"]');
                const originalButtonText = submitButton.innerHTML;
                submitButton.disabled = true;
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Inscription en cours...';
                
                fetch(signupForm.action, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        const successAlert = document.createElement('div');
                        successAlert.className = 'alert alert-success';
                        successAlert.textContent = data.message;
                        signupForm.insertBefore(successAlert, signupForm.firstChild);
                        
                        // Redirect to login page after 2 seconds
                        setTimeout(() => {
                            window.location.href = '/auth/login.php?message=' + encodeURIComponent(data.message);
                        }, 2000);
                    } else {
                        // Show error message
                        const errorAlert = document.createElement('div');
                        errorAlert.className = 'alert alert-danger';
                        errorAlert.textContent = data.message;
                        signupForm.insertBefore(errorAlert, signupForm.firstChild);
                        
                        // Re-enable submit button
                        submitButton.disabled = false;
                        submitButton.innerHTML = originalButtonText;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    const errorAlert = document.createElement('div');
                    errorAlert.className = 'alert alert-danger';
                    errorAlert.textContent = "Une erreur est survenue lors de l'inscription";
                    signupForm.insertBefore(errorAlert, signupForm.firstChild);
                    
                    // Re-enable submit button
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonText;
                });
            } else {
                // Show validation errors
                const errorAlert = document.createElement('div');
                errorAlert.className = 'alert alert-danger';
                errorAlert.innerHTML = '<ul class="mb-0"><li>' + errorMessages.join('</li><li>') + '</li></ul>';
                signupForm.insertBefore(errorAlert, signupForm.firstChild);
            }
        });
    }
});

// Password visibility toggle
document.addEventListener('DOMContentLoaded', function() {
    const toggleButtons = document.querySelectorAll('.password-toggle');
    
    toggleButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const input = this.closest('.input-group').querySelector('input');
            const icon = this.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });
});

// Helper functions
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email.toLowerCase());
}

function showError(input) {
    input.classList.add('is-invalid');
    const errorDiv = input.nextElementSibling;
    if (errorDiv && errorDiv.classList.contains('error-message')) {
        errorDiv.style.display = 'block';
    }
}

function hideError(input) {
    input.classList.remove('is-invalid');
    const errorDiv = input.nextElementSibling;
    if (errorDiv && errorDiv.classList.contains('error-message')) {
        errorDiv.style.display = 'none';
    }
}

// Dashboard functionality
document.addEventListener('DOMContentLoaded', function() {
    const newAmendmentBtn = document.getElementById('newAmendmentBtn');
    const amendmentDialog = document.getElementById('amendmentDialog');
    
    // Initialize dashboard components
    if (document.querySelector('.chair-dashboard')) {
        initChairDashboard();
    }
});

function initChairDashboard() {
    initSelect2();
    initDelegateSearch();
    initAmendmentFilters();
    initAmendmentActions();
    updateStats();
}

function initSelect2() {
    if ($.fn.select2) {
        $('.select2').select2({
            theme: 'bootstrap4'
        });
    }
}

function initDelegateSearch() {
    const searchInput = document.getElementById('delegateSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            document.querySelectorAll('.delegate-card').forEach(card => {
                const name = card.querySelector('.delegate-name').textContent.toLowerCase();
                const country = card.querySelector('.delegate-country').textContent.toLowerCase();
                card.style.display = (name.includes(searchTerm) || country.includes(searchTerm)) ? '' : 'none';
            });
        });
    }
}

function initAmendmentFilters() {
    const filterBtns = document.querySelectorAll('.amendment-filter');
    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            filterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            filterAmendments(this.dataset.filter);
        });
    });
}

function filterAmendments(filter) {
    const amendments = document.querySelectorAll('.amendment-card');
    amendments.forEach(amendment => {
        const status = amendment.dataset.status;
        if (filter === 'all' || status === filter) {
            amendment.style.display = '';
        } else {
            amendment.style.display = 'none';
        }
    });
}

function initAmendmentActions() {
    document.querySelectorAll('.accept-amendment').forEach(btn => {
        btn.addEventListener('click', function() {
            const card = this.closest('.amendment-card');
            acceptAmendment(card);
        });
    });

    document.querySelectorAll('.reject-amendment').forEach(btn => {
        btn.addEventListener('click', function() {
            const card = this.closest('.amendment-card');
            rejectAmendment(card);
        });
    });
}

function acceptAmendment(amendmentCard) {
    updateAmendmentStatus(amendmentCard, 'accepted');
}

function rejectAmendment(amendmentCard) {
    updateAmendmentStatus(amendmentCard, 'rejected');
}

function updateAmendmentStatus(amendmentCard, status) {
    amendmentCard.dataset.status = status;
    amendmentCard.querySelector('.status-badge').textContent = status.charAt(0).toUpperCase() + status.slice(1);
    updateStats();
}

function updateStats() {
    const amendments = document.querySelectorAll('.amendment-card');
    const stats = {
        total: amendments.length,
        pending: 0,
        accepted: 0,
        rejected: 0
    };

    amendments.forEach(amendment => {
        stats[amendment.dataset.status]++;
    });

    Object.keys(stats).forEach(key => {
        const element = document.getElementById(`${key}Count`);
        if (element) {
            element.textContent = stats[key];
        }
    });
}

// Chair Dashboard Functionality
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('.chair-dashboard')) {
        initChairDashboard();
    }
});

function initChairDashboard() {
    initSelect2();
    initDelegateSearch();
    initAmendmentFilters();
    initAmendmentActions();
}

function initSelect2() {
    $('.country-select').select2({
        placeholder: "Attribuer un pays",
        allowClear: true,
        width: '100%'
    });
}

function initDelegateSearch() {
    const searchInput = document.querySelector('.search-bar input');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const delegateRows = document.querySelectorAll('.delegate-row');
            
            delegateRows.forEach(row => {
                const delegateName = row.querySelector('h5').textContent.toLowerCase();
                const delegateEmail = row.querySelector('small').textContent.toLowerCase();
                
                if (delegateName.includes(searchTerm) || delegateEmail.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
}

function initAmendmentFilters() {
    const filterButtons = document.querySelectorAll('.filter-button');
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons
            filterButtons.forEach(btn => btn.classList.remove('active'));
            // Add active class to clicked button
            this.classList.add('active');
            
            const filter = this.textContent.trim().toLowerCase();
            filterAmendments(filter);
        });
    });
}

function filterAmendments(filter) {
    const amendments = document.querySelectorAll('.amendment-card');
    amendments.forEach(amendment => {
        switch(filter) {
            case 'en attente':
                amendment.style.display = !amendment.classList.contains('accepted') && 
                                       !amendment.classList.contains('rejected') ? '' : 'none';
                break;
            case 'acceptés':
                amendment.style.display = amendment.classList.contains('accepted') ? '' : 'none';
                break;
            case 'rejetés':
                amendment.style.display = amendment.classList.contains('rejected') ? '' : 'none';
                break;
            default: // 'tous'
                amendment.style.display = '';
        }
    });
}

function initAmendmentActions() {
    // Accept Amendment
    document.querySelectorAll('.btn-accept').forEach(button => {
        button.addEventListener('click', function() {
            const amendmentCard = this.closest('.amendment-card');
            acceptAmendment(amendmentCard);
        });
    });

    // Reject Amendment
    document.querySelectorAll('.btn-reject').forEach(button => {
        button.addEventListener('click', function() {
            const amendmentCard = this.closest('.amendment-card');
            rejectAmendment(amendmentCard);
        });
    });
}

function acceptAmendment(amendmentCard) {
    amendmentCard.classList.remove('rejected');
    amendmentCard.classList.add('accepted');
    updateAmendmentStatus(amendmentCard, 'accepted');
    
    // Update UI
    const actions = amendmentCard.querySelector('.amendment-actions');
    actions.innerHTML = '<span class="text-success"><i class="fas fa-check-circle me-2"></i>Accepté</span>';
    
    // Update stats
    updateStats();
}

function rejectAmendment(amendmentCard) {
    amendmentCard.classList.remove('accepted');
    amendmentCard.classList.add('rejected');
    updateAmendmentStatus(amendmentCard, 'rejected');
    
    // Update UI
    const actions = amendmentCard.querySelector('.amendment-actions');
    actions.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle me-2"></i>Rejeté</span>';
    
    // Update stats
    updateStats();
}

function updateAmendmentStatus(amendmentCard, status) {
    const amendmentId = amendmentCard.querySelector('h5').textContent.split('#')[1];
    // TODO: Implement API call to update amendment status
    console.log(`Amendment ${amendmentId} ${status}`);
}

function updateStats() {
    const stats = {
        pending: document.querySelectorAll('.amendment-card:not(.accepted):not(.rejected)').length,
        accepted: document.querySelectorAll('.amendment-card.accepted').length,
        rejected: document.querySelectorAll('.amendment-card.rejected').length
    };
    
    // Update pending amendments stat
    const pendingStatElement = document.querySelector('.stat-number');
    if (pendingStatElement) {
        pendingStatElement.textContent = stats.pending;
    }
}

// Admin Dashboard Functionality
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('.admin-dashboard')) {
        initAdminDashboard();
    }
});

function initAdminDashboard() {
    initRoleSwitcher();
    initParticipantManagement();
    initResolutionManagement();
    initLiveUpdates();
}

function initRoleSwitcher() {
    const roleSwitcher = document.getElementById('roleSwitcher');
    if (roleSwitcher) {
        roleSwitcher.addEventListener('change', function(e) {
            const userId = this.dataset.userId;
            const newRole = this.value;
            
            fetch('/api/v1/users/update-role', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    userId: userId,
                    role: newRole
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Rôle mis à jour avec succès', 'success');
                } else {
                    showNotification('Erreur lors de la mise à jour du rôle', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Erreur lors de la mise à jour du rôle', 'error');
            });
        });
    }
}

function initParticipantManagement() {
    const participantList = document.querySelector('.participant-list');
    if (participantList) {
        // Gérer les actions sur les participants
        participantList.addEventListener('click', function(e) {
            if (e.target.matches('.suspend-user, .activate-user, .delete-user')) {
                e.preventDefault();
                const userId = e.target.dataset.userId;
                const action = e.target.dataset.action;
                
                updateParticipantStatus(userId, action);
            }
        });
    }
}

function initResolutionManagement() {
    const resolutionList = document.querySelector('.resolution-list');
    if (resolutionList) {
        resolutionList.addEventListener('click', function(e) {
            if (e.target.matches('.resolution-settings')) {
                e.preventDefault();
                const title = e.target.dataset.title;
                showResolutionSettings(title);
            }
        });
    }
}

function showResolutionSettings(resolutionTitle) {
    // Afficher la modal des paramètres de résolution
    const modal = new bootstrap.Modal(document.getElementById('resolutionSettingsModal'));
    modal.show();
}

function initLiveUpdates() {
    // Initialiser la connexion WebSocket pour les mises à jour en direct
    const ws = new WebSocket('ws://' + window.location.host + '/ws');
    
    ws.onmessage = function(event) {
        const data = JSON.parse(event.data);
        
        switch(data.type) {
            case 'new_participant':
                updateParticipantList(data.participant);
                break;
            case 'status_change':
                updateParticipantStatus(data.userId, data.status);
                break;
            case 'new_resolution':
                updateResolutionList(data.resolution);
                break;
        }
    };
}

function showNotification(message, type) {
    const toast = document.createElement('div');
    toast.className = `toast show position-fixed bottom-0 end-0 m-3 bg-${type}`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="toast-body text-white">
            ${message}
        </div>
    `;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

// Google Docs Integration
class DocsManager {
    constructor() {
        this.docsUrl = '';
        this.lastModified = null;
        this.checkInterval = 5000; // Check every 5 seconds
        this.initElements();
        this.initEventListeners();
        this.startAutoCheck();
    }

    initElements() {
        this.urlInput = document.getElementById('docsUrl');
        this.syncButton = document.getElementById('syncDocs');
        this.statusIndicator = document.getElementById('syncStatus');
        this.viewModeToggle = document.getElementById('viewMode');
    }

    initEventListeners() {
        if (this.syncButton) {
            this.syncButton.addEventListener('click', () => this.updateDocument());
        }
        
        if (this.viewModeToggle) {
            this.viewModeToggle.addEventListener('change', () => {
                this.updateViewMode(this.viewModeToggle.checked ? 'edit' : 'view');
            });
        }
    }

    formatGoogleDocsUrl(url) {
        // Convertir l'URL en format d'intégration si nécessaire
        if (url.includes('docs.google.com')) {
            if (!url.includes('/embed')) {
                url = url.replace('/edit', '/embed');
            }
        }
        return url;
    }

    validateUrl() {
        const url = this.urlInput.value.trim();
        if (!url) {
            this.showStatus('error', 'Veuillez entrer une URL');
            return false;
        }
        if (!url.includes('docs.google.com')) {
            this.showStatus('error', 'URL invalide. Veuillez entrer une URL Google Docs valide');
            return false;
        }
        return true;
    }

    updateDocument() {
        if (!this.validateUrl()) return;
        
        this.showLoading(true);
        const formattedUrl = this.formatGoogleDocsUrl(this.urlInput.value);
        
        fetch('/api/v1/docs/update', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                url: formattedUrl
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.docsUrl = formattedUrl;
                this.lastModified = new Date();
                this.showStatus('success', 'Document mis à jour avec succès');
                this.broadcastUpdate();
            } else {
                this.showStatus('error', data.message || 'Erreur lors de la mise à jour');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            this.showStatus('error', 'Erreur lors de la mise à jour');
        })
        .finally(() => {
            this.showLoading(false);
        });
    }

    showLoading(show) {
        if (this.syncButton) {
            this.syncButton.disabled = show;
            this.syncButton.innerHTML = show ? 
                '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Synchronisation...' : 
                'Synchroniser';
        }
    }

    showStatus(type, message) {
        if (this.statusIndicator) {
            this.statusIndicator.className = `alert alert-${type} mt-3`;
            this.statusIndicator.textContent = message;
        }
    }

    updateSyncStatus(status) {
        const indicator = document.getElementById('lastSync');
        if (indicator) {
            indicator.textContent = status;
        }
    }

    updateViewMode(mode) {
        const frame = document.getElementById('docsFrame');
        if (frame && this.docsUrl) {
            const url = new URL(this.docsUrl);
            if (mode === 'edit') {
                url.searchParams.set('rm', 'minimal');
            } else {
                url.searchParams.delete('rm');
            }
            frame.src = url.toString();
        }
    }

    startAutoCheck() {
        setInterval(() => this.checkForUpdates(), this.checkInterval);
    }

    checkForUpdates() {
        if (!this.docsUrl) return;
        
        fetch('/api/v1/docs/check-updates', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                url: this.docsUrl,
                lastModified: this.lastModified?.toISOString()
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.hasUpdates) {
                this.updateDocument();
            }
        })
        .catch(error => {
            console.error('Error checking for updates:', error);
        });
    }

    broadcastUpdate() {
        // Envoyer une mise à jour via WebSocket aux autres utilisateurs
        if (window.ws) {
            window.ws.send(JSON.stringify({
                type: 'docs_update',
                url: this.docsUrl,
                timestamp: new Date().toISOString()
            }));
        }
    }
}

// Initialize Docs Manager when available
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('.docs-manager')) {
        window.docsManager = new DocsManager();
    }
});
