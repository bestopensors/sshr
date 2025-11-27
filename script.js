// Global translation function
function getTranslation(key) {
  const currentLang = document.documentElement.getAttribute('data-lang') || 'hr';
  const translations = {
    hr: {
      'error-name-required': 'Ime je obavezno',
      'error-email-required': 'Email adresa je obavezna',
      'error-message-required': 'Poruka je obavezna',
      'error-form-validation': 'Molimo ispravite greške u formi prije slanja.',
      'contact-form-robot-error': 'Molimo potvrdite da niste robot',
      'contact-form-robot-text': 'Ja sam čovjek',
      'contact-form-gdpr-error': 'Morate se složiti s Politikom privatnosti i Uvjetima korištenja',
      'contact-form-success': 'Poruka je uspješno poslana! Odgovorit ćemo vam uskoro.'
    },
    en: {
      'error-name-required': 'Name is required',
      'error-email-required': 'Email address is required',
      'error-message-required': 'Message is required',
      'error-form-validation': 'Please fix the errors in the form before submitting.',
      'contact-form-robot-error': 'Please verify that you are not a robot',
      'contact-form-robot-text': 'I am human',
      'contact-form-gdpr-error': 'You must agree to the Privacy Policy and Terms of Use',
      'contact-form-success': 'Message sent successfully! We will respond to you soon.'
    }
  };
  
  return translations[currentLang][key] || key;
}

// Modern JavaScript with ES6+ features
class PortfolioApp {
  constructor() {
    this.init();
  }

  init() {
    this.setupNavigation();
    this.setupScrollEffects();
    this.setupFormInteractions();
    this.setupAnimations();
    this.setupThemeToggle();
    this.setupLanguageToggle();
    this.setupFAQ();
    this.initializeLanguage();
    this.initializeTheme();
  }

  setupNavigation() {
    const navToggle = document.getElementById('nav-toggle');
    const navClose = document.getElementById('nav-close');
    const navMobile = document.getElementById('nav-mobile');
    
    if (navToggle && navMobile) {
      navToggle.addEventListener('click', () => {
        navMobile.classList.add('nav__mobile--active');
        navToggle.classList.add('nav__toggle--active');
        document.body.classList.add('nav-open');
      });
    }
    
    if (navClose && navMobile) {
      navClose.addEventListener('click', () => {
        navMobile.classList.remove('nav__mobile--active');
        navToggle.classList.remove('nav__toggle--active');
        document.body.classList.remove('nav-open');
      });
    }

    // Smooth scrolling for navigation links
    document.querySelectorAll('.nav__link, .footer__link').forEach(link => {
      link.addEventListener('click', (e) => {
        const targetId = link.getAttribute('href');
        
        // Check if it's an external link (starts with http or is a file)
        if (targetId && (targetId.startsWith('http') || targetId.includes('.html'))) {
          // Let the browser handle external links normally
          return;
        }
        
        // Handle internal anchor links
        if (targetId && targetId.startsWith('#')) {
          e.preventDefault();
          const targetElement = document.querySelector(targetId);
          
          if (targetElement) {
            targetElement.scrollIntoView({ 
              behavior: 'smooth',
              block: 'start'
            });
            
            // Close mobile menu if open
            if (navMobile && navMobile.classList.contains('nav__mobile--active')) {
              navMobile.classList.remove('nav__mobile--active');
              navToggle.classList.remove('nav__toggle--active');
              document.body.classList.remove('nav-open');
            }
          }
        }
      });
    });
  }

  setupScrollEffects() {
    // Intersection Observer for animations
    const observerOptions = {
      threshold: 0.1,
      rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('animate-in');
        }
      });
    }, observerOptions);

    // Observe elements for animation
    document.querySelectorAll('.package-card, .contact__item, .hero__text, .hero__visual, .value-card, .team-member, .stat-item').forEach(el => {
      observer.observe(el);
    });

    // Header scroll effect
    let lastScrollY = window.scrollY;
    const header = document.querySelector('.header');
    
    window.addEventListener('scroll', () => {
      const currentScrollY = window.scrollY;
      
      if (currentScrollY > 100) {
        header.classList.add('header--scrolled');
      } else {
        header.classList.remove('header--scrolled');
      }
      
      lastScrollY = currentScrollY;
    });

    // Navbar active state management
    this.setupNavbarActiveState();
  }

  setupFormInteractions() {
    // Floating label effect
    document.querySelectorAll('.form__input').forEach(input => {
      input.addEventListener('focus', () => {
        input.parentElement.classList.add('form__group--focused');
      });

      input.addEventListener('blur', () => {
        if (!input.value) {
          input.parentElement.classList.remove('form__group--focused');
        }
      });

      // Check if input has value on load
      if (input.value) {
        input.parentElement.classList.add('form__group--focused');
      }
    });


    // Turnstile validation
    let turnstileValid = false;
    let turnstileToken = null;

    // Turnstile callback functions
    window.onTurnstileSuccess = function(token) {
      turnstileValid = true;
      turnstileToken = token;
      const robotError = document.getElementById('robot-error');
      if (robotError) {
        robotError.style.display = 'none';
      }
    };

    window.onTurnstileError = function() {
      turnstileValid = false;
      turnstileToken = null;
      const robotError = document.getElementById('robot-error');
      if (robotError) {
        robotError.textContent = getTranslation('contact-form-robot-error');
        robotError.style.display = 'block';
      }
    };

    window.onTurnstileExpired = function() {
      turnstileValid = false;
      turnstileToken = null;
    };


    // Form validation and submission
    const form = document.getElementById('contact-form');
    if (form) {
      // Real-time validation
      const inputs = form.querySelectorAll('.form__input');
      inputs.forEach(input => {
        input.addEventListener('blur', () => {
          validateField(input);
        });
        
        input.addEventListener('input', () => {
          clearFieldError(input);
        });
      });

      // Form submission
      form.addEventListener('submit', (e) => {
        e.preventDefault();
        
        // Validate all fields
        let isValid = true;
        inputs.forEach(input => {
          if (!validateField(input)) {
            isValid = false;
          }
        });

        // Validate Turnstile
        if (!turnstileValid) {
          const robotError = document.getElementById('robot-error');
          if (robotError) {
            robotError.textContent = getTranslation('contact-form-robot-error');
            robotError.style.display = 'block';
          }
          isValid = false;
        }

        // Validate GDPR consent
        const gdprConsent = document.getElementById('gdpr-consent');
        if (!gdprConsent.checked) {
          const gdprError = document.getElementById('gdpr-error');
          if (gdprError) {
            gdprError.textContent = getTranslation('contact-form-gdpr-error');
            gdprError.style.display = 'block';
          }
          isValid = false;
        }

        if (isValid) {
          // Submit form data to server
          const formData = new FormData(form);
          formData.append('cf-turnstile-response', turnstileToken || '');
          
          // Disable submit button
          const submitBtn = form.querySelector('button[type="submit"]');
          if (submitBtn) submitBtn.disabled = true;
          
          fetch('contact-handler.php', {
            method: 'POST',
            body: formData
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              showSuccessMessage();
              form.reset();
              // Reset Turnstile
              if (typeof window.turnstileWidgetId !== 'undefined' && window.turnstileWidgetId !== null && typeof turnstile !== 'undefined') {
                turnstile.reset(window.turnstileWidgetId);
                turnstileValid = false;
                turnstileToken = null;
              }
              // Reset all form groups to unfocused state
              document.querySelectorAll('.form__group').forEach(group => {
                group.classList.remove('form__group--focused');
              });
            } else {
              showErrorMessage();
              console.error('Form submission failed:', data);
            }
          })
          .catch(error => {
            showErrorMessage();
            console.error('Form submission error:', error);
          })
          .finally(() => {
            if (submitBtn) submitBtn.disabled = false;
          });
        } else {
          // Show error message
          showErrorMessage();
        }
      });
    }

    // Validation functions
    const validateField = (field) => {
      const value = field.value.trim();
      const fieldName = field.name;
      const errorElement = document.getElementById(`${fieldName}-error`);
      let isValid = true;
      let errorMessage = '';

      // Clear previous errors
      clearFieldError(field);

      // Name validation
      if (fieldName === 'name') {
        if (!value) {
          errorMessage = getTranslation('error-name-required');
          isValid = false;
        } else if (value.length < 2) {
          errorMessage = 'Ime mora imati najmanje 2 znaka';
          isValid = false;
        } else if (!/^[a-zA-ZčćšđžČĆŠĐŽ\s]+$/.test(value)) {
          errorMessage = 'Ime može sadržavati samo slova';
          isValid = false;
        }
      }

      // Email validation
      if (fieldName === 'email') {
        if (!value) {
          errorMessage = getTranslation('error-email-required');
          isValid = false;
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
          errorMessage = 'Molimo unesite valjanu email adresu';
          isValid = false;
        }
      }

      // Phone validation (optional)
      if (fieldName === 'phone') {
        if (value && !/^[\+]?[0-9\s\-\(\)]{8,15}$/.test(value)) {
          errorMessage = 'Molimo unesite valjan broj telefona';
          isValid = false;
        }
      }

      // Message validation
      if (fieldName === 'message') {
        if (!value) {
          errorMessage = getTranslation('error-message-required');
          isValid = false;
        } else if (value.length < 10) {
          errorMessage = 'Poruka mora imati najmanje 10 znakova';
          isValid = false;
        } else if (value.length > 1000) {
          errorMessage = 'Poruka ne smije imati više od 1000 znakova';
          isValid = false;
        }
      }


      // Show error if validation failed
      if (!isValid) {
        showFieldError(field, errorMessage);
      }

      return isValid;
    };

    function showFieldError(field, message) {
      const errorElement = document.getElementById(`${field.name}-error`);
      if (errorElement) {
        errorElement.textContent = message;
        errorElement.style.display = 'block';
      }
      field.classList.add('form__input--error');
      field.parentElement.classList.add('form__group--error');
    }

    function clearFieldError(field) {
      const errorElement = document.getElementById(`${field.name}-error`);
      if (errorElement) {
        errorElement.textContent = '';
        errorElement.style.display = 'none';
      }
      field.classList.remove('form__input--error');
      field.parentElement.classList.remove('form__group--error');
    }

    function showSuccessMessage() {
      // Create success notification
      const notification = document.createElement('div');
      notification.className = 'form__notification form__notification--success';
      notification.innerHTML = `
        <div class="notification__content">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22,4 12,14.01 9,11.01"></polyline>
          </svg>
          <span>${getTranslation('contact-form-success')}</span>
        </div>
      `;
      
      document.body.appendChild(notification);
      
      // Show notification
      setTimeout(() => {
        notification.classList.add('form__notification--show');
      }, 100);
      
      // Remove notification after 5 seconds
      setTimeout(() => {
        notification.classList.remove('form__notification--show');
        setTimeout(() => {
          document.body.removeChild(notification);
        }, 300);
      }, 5000);
    }

    function showErrorMessage() {
      console.log('Creating error notification');
      // Create error notification
      const notification = document.createElement('div');
      notification.className = 'form__notification form__notification--error';
      notification.innerHTML = `
        <div class="notification__content">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="15" y1="9" x2="9" y2="15"></line>
            <line x1="9" y1="9" x2="15" y2="15"></line>
          </svg>
          <span>${getTranslation('error-form-validation')}</span>
        </div>
      `;
      
      document.body.appendChild(notification);
      console.log('Notification added to DOM');
      
      // Show notification
      setTimeout(() => {
        notification.classList.add('form__notification--show');
        console.log('Notification shown');
      }, 100);
      
      // Remove notification after 5 seconds
      setTimeout(() => {
        notification.classList.remove('form__notification--show');
        setTimeout(() => {
          document.body.removeChild(notification);
        }, 300);
      }, 5000);
    }

    // Email button click functionality
    document.querySelectorAll('.btn--primary').forEach(button => {
      if (button.textContent.includes('Kontaktirajte nas') || button.textContent.includes('Kontaktirajte')) {
        button.addEventListener('click', (e) => {
          e.preventDefault();
          window.location.href = 'mailto:info@startsmarthr.eu';
        });
      }
    });
  }

  setupAnimations() {
    // Button ripple effect
    document.querySelectorAll('.btn').forEach(button => {
      button.addEventListener('click', (e) => {
        const ripple = button.querySelector('.btn__ripple');
        if (ripple) {
          const rect = button.getBoundingClientRect();
          const size = Math.max(rect.width, rect.height);
          const x = e.clientX - rect.left - size / 2;
          const y = e.clientY - rect.top - size / 2;
          
          ripple.style.width = ripple.style.height = size + 'px';
          ripple.style.left = x + 'px';
          ripple.style.top = y + 'px';
          ripple.classList.add('btn__ripple--active');
          
          setTimeout(() => {
            ripple.classList.remove('btn__ripple--active');
          }, 600);
        }
      });
    });
  }

  setupThemeToggle() {
    const themeToggle = document.getElementById('theme-toggle');
    const html = document.documentElement;
    
    if (themeToggle) {
      themeToggle.addEventListener('click', () => {
        const currentTheme = html.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        html.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        
        // Update Turnstile with new theme
        if (typeof window.updateTurnstile === 'function') {
          window.updateTurnstile();
        }
        
        // Log theme change
        console.log(`Theme changed to: ${newTheme}`);
      });
    }
  }

  initializeTheme() {
    const html = document.documentElement;
    
    // Check for saved theme preference or default to dark mode
    const savedTheme = localStorage.getItem('theme');
    const currentTheme = savedTheme || 'dark';
    
    html.setAttribute('data-theme', currentTheme);
    
    // Log current theme
    console.log(`Current theme: ${currentTheme}`);
    
    // If no saved theme, default to dark mode
    if (!savedTheme) {
      html.setAttribute('data-theme', 'dark');
      localStorage.setItem('theme', 'dark');
      console.log(`Default theme set to: dark`);
    }
  }

  setupLanguageToggle() {
    const langFlags = document.querySelectorAll('.nav__lang-flag');
    
    langFlags.forEach(flag => {
      flag.addEventListener('click', () => {
        const lang = flag.getAttribute('data-lang');
        this.switchLanguage(lang);
      });
    });
    
    // Check if emojis are supported and show fallbacks if not
    this.checkEmojiSupport();
  }

  setupFAQ() {
    const faqItems = document.querySelectorAll('.faq__item');
    
    faqItems.forEach(item => {
      const question = item.querySelector('.faq__question');
      
      if (question) {
        question.addEventListener('click', (e) => {
          e.preventDefault();
          e.stopPropagation();
          
          const isActive = item.classList.contains('faq__item--active');
          
          // Close all other FAQ items
          faqItems.forEach(otherItem => {
            if (otherItem !== item) {
              otherItem.classList.remove('faq__item--active');
            }
          });
          
          // Toggle current item
          if (isActive) {
            item.classList.remove('faq__item--active');
          } else {
            item.classList.add('faq__item--active');
          }
        });
      }
    });
  }

  switchLanguage(lang) {
    const html = document.documentElement;
    html.setAttribute('data-lang', lang);
    localStorage.setItem('language', lang);
    this.updateActiveFlag(lang);
    this.translatePage(lang);
    
    // Update Turnstile with new language and theme
    if (typeof window.updateTurnstile === 'function') {
      window.updateTurnstile();
    }
  }

  getTranslation(key) {
    const currentLang = document.documentElement.getAttribute('data-lang') || 'hr';
    const translations = {
      hr: {
        'error-name-required': 'Ime je obavezno',
        'error-email-required': 'Email adresa je obavezna',
        'error-message-required': 'Poruka je obavezna',
        'error-form-validation': 'Molimo ispravite greške u formi prije slanja.'
      },
      en: {
        'error-name-required': 'Name is required',
        'error-email-required': 'Email address is required',
        'error-message-required': 'Message is required',
        'error-form-validation': 'Please correct errors in the form before submitting.'
      }
    };
    return translations[currentLang][key] || key;
  }

  setupNavbarActiveState() {
    const sections = [
      { id: 'hero', selector: '.hero' },
      { id: 'packages', selector: '.packages' },
      { id: 'contact', selector: '.contact' },
      { id: 'faq', selector: '.faq' }
    ];

    const navLinks = document.querySelectorAll('.nav__link[href^="#"]');
    
    // Remove active class from all nav links
    const clearActiveLinks = () => {
      navLinks.forEach(link => {
        link.classList.remove('nav__link--active');
      });
    };

    // Set active nav link
    const setActiveLink = (sectionId) => {
      clearActiveLinks();
      const activeLink = document.querySelector(`.nav__link[href="#${sectionId}"]`);
      if (activeLink) {
        activeLink.classList.add('nav__link--active');
        console.log('Active link set for:', sectionId);
      }
    };

    // Intersection Observer for sections
    const observerOptions = {
      rootMargin: '-10% 0px -10% 0px',
      threshold: 0.3
    };

    const sectionObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const sectionId = entry.target.id;
          console.log('Section in view:', sectionId);
          setActiveLink(sectionId);
        }
      });
    }, observerOptions);

    // Observe all sections
    sections.forEach(section => {
      const element = document.querySelector(section.selector);
      if (element) {
        console.log('Observing section:', section.id, element);
        sectionObserver.observe(element);
      } else {
        console.log('Section not found:', section.selector);
      }
    });

    // Handle manual navigation clicks
    navLinks.forEach(link => {
      link.addEventListener('click', (e) => {
        const href = link.getAttribute('href');
        if (href.startsWith('#')) {
          const sectionId = href.substring(1);
          console.log('Manual navigation to:', sectionId);
          setActiveLink(sectionId);
        }
      });
    });
  }

  updateActiveFlag(lang) {
    const langFlags = document.querySelectorAll('.nav__lang-flag');
    
    langFlags.forEach(flag => {
      flag.classList.remove('active');
      if (flag.getAttribute('data-lang') === lang) {
        flag.classList.add('active');
      }
    });
  }

  checkEmojiSupport() {
    // Using CSS-based flag symbols that work reliably
    console.log('Language selector flags loaded - CSS-based symbols');
  }

  initializeLanguage() {
    const html = document.documentElement;
    
    // Check for saved language preference or default to Croatian
    const currentLang = localStorage.getItem('language') || 'hr';
    html.setAttribute('data-lang', currentLang);
    
    // Update active flag
    this.updateActiveFlag(currentLang);
    
    // Initial translation
    this.translatePage(currentLang);
  }

  translatePage(lang) {
    // Translation object
    const translations = {
      hr: {
        // Navigation
        'nav-home': 'Početna',
        'nav-packages': 'Paketi', 
        'nav-contact': 'Kontakt',
        'nav-faq': 'FAQ',
        'nav-about': 'O nama',
        // Hero section
        'hero-subtitle': 'Dobrodošli u budućnost',
        'hero-title-1': 'Profesionalni',
        'hero-title-2': 'Web Dizajn',
        'hero-title-3': 'za vaš uspjeh',
        'hero-description': 'Transformirajte svoje ideje u digitalnu stvarnost. Kreirajmo zajedno web stranice koje privlače, konvertiraju i rastu.',
        'hero-feature-1': 'Brza izrada',
        'hero-feature-2': 'Responzivni dizajn',
        'hero-feature-3': 'Moderan dizajn',
        'hero-cta': 'Saznajte više',
        'hero-scroll': 'Scroll',
        // Packages section
        'packages-title': 'Naši paketi',
        'packages-subtitle': 'Odaberite paket koji odgovara vašim potrebama',
        // Contact section
        'contact-title': 'Kontaktirajte nas',
        'contact-subtitle': 'Spremni smo odgovoriti na sva vaša pitanja',
        'contact-email': 'Email',
        'contact-phone': 'Telefon',
        'contact-location': 'Lokacija',
        'contact-form-name': 'Vaše ime',
        'contact-form-email': 'Vaš email',
        'contact-form-phone': 'Broj telefona',
        'contact-form-message': 'Vaša poruka',
        'contact-form-robot': 'Nisam robot',
        'contact-form-robot-error': 'Molimo potvrdite da niste robot',
        'contact-form-robot-text': 'Ja sam čovjek',
        'contact-form-gdpr': 'Slažem se s <a href="privacy.html" target="_blank" class="form__link" onclick="event.stopPropagation()">Politikom privatnosti</a> i <a href="terms.html" target="_blank" class="form__link" onclick="event.stopPropagation()">Uvjetima korištenja</a> te dozvoljavam obradu mojih osobnih podataka u svrhu odgovora na moj upit.',
        'contact-form-gdpr-error': 'Morate se složiti s Politikom privatnosti i Uvjetima korištenja',
        'contact-form-success': 'Poruka je uspješno poslana! Odgovorit ćemo vam uskoro.',
        'contact-form-submit': 'Pošalji poruku',
        // Package cards
        'package-basic-title': 'Osnovna Stranica',
        'package-pro-title': 'Profesionalna Stranica',
        'package-premium-title': 'Premium Stranica',
        'package-custom-title': 'Custom Projekt',
        'package-visit-first': 'Posjetite stranicu',
        'package-visit-second': 'Posjetite stranicu',
        'package-visit-site': 'Posjetite stranicu',
        'package-details': 'Detalji',
        'package-details-hover': 'Prikaži detalje',
        'package-details-hide': 'Sakrij detalje',
        'package-optional-services': 'Dodatne mjesečne usluge',
        'package-optional-services-hide': 'Sakrij dodatne usluge',
        'optional-services-title': 'Dodatne mjesečne usluge:',
        'optional-service-maintenance': 'Održavanje stranice',
        'optional-service-maintenance-desc': 'Redovita sigurnosna ažuriranja, zaštita od hakiranja, DDoS zaštita, SSL certifikati, backup sustavi, praćenje performansi, optimizacija brzine, ažuriranje plugina i frameworka, tehnička podrška 24/7',
        'optional-service-content-changes': 'Osnovne promjene sadržaja',
        'optional-service-content-changes-desc': 'Promjene teksta i slika na vašoj stranici',
        'pricing-month': ' mjesečno',
        'pricing-by-agreement': 'po dogovoru',
        'feature-bugs-security': '7 dana besplatnih bug fixova i sigurnosnih ažuriranja',
        'details-title': 'Dodatni detalji:',
        'details-basic-1': 'Jednostavna navigacija',
        'details-basic-2': 'Kontakt forma',
        'details-basic-3': 'Osnovne animacije',
        'details-basic-4': 'Prilagođen layout',
        'details-pro-1': 'Responzivan dizajn s naprednom animacijom',
        'details-pro-2': 'CMS integracija',
        'details-pro-3': 'Blog i galerija',
        'details-pro-4': 'Brza optimizacija performansi',
        'details-premium-1': 'Prilagođene API integracije',
        'details-premium-2': 'Višekorisnički sustav (Login/Signup)',
        'details-premium-3': 'Personalizirani dizajn',
        'details-premium-4': 'Vrhunska korisnička podrška',
        'details-custom-1': 'Individualna konzultacija',
        'details-custom-2': 'Prilagođene integracije',
        'details-custom-3': 'Napredna sigurnost',
        'details-custom-4': 'Kontinuirana podrška',
        'hero-contact': 'Kontaktirajte nas',
        // Package descriptions
        'package-basic-desc': 'Pojednostavljeno rješenje za vaš prvi online korak. Idealno za osobne projekte i male tvrtke.',
        'package-pro-desc': 'Napredno rješenje s modernim CMS-om i interaktivnim elementima, idealno za srednje i velike tvrtke.',
        'package-premium-desc': 'Kompletno rješenje s najnovijim tehnologijama, prilagođeno kompleksnim zahtjevima i integracijama.',
        'package-custom-desc': 'Potpuno prilagođeno rješenje za velike projekte s jedinstvenim zahtjevima. Kontaktirajte nas za detalje.',
        'package-custom-eta': 'ETA: Po dogovoru',
        'badge-custom': 'Custom',
        'pricing-contact': 'Kontaktirajte nas',
        'pricing-custom': 'za ponudu',
        'package-contact-us': 'Kontaktirajte nas',
        'feature-custom-design': 'Potpuno prilagođen dizajn',
        'feature-custom-functionality': 'Prilagođena funkcionalnost',
        'feature-dedicated-support': 'Dedicirani tim za podršku',
        'feature-scalable': 'Skalabilna arhitektura',
        // About page translations
        'about-subtitle': 'O nama',
        'about-title': 'Naša priča',
        'about-subtitle-text': 'Stvaramo digitalna rješenja koja pokreću vaše poslovanje',
        'about-description': 'Start Smart HR je tim stručnjaka posvećenih stvaranju izvanrednih web stranica koje ne samo da izgledaju odlično, već i donose rezultate. Naša misija je pomoći vašem poslovanju da se istakne u digitalnom svijetu.',
        'values-title': 'Naše vrijednosti',
        'values-subtitle': 'Temelj našeg rada su vrijednosti koje nas vode u svakom projektu',
        'value-quality-title': 'Kvaliteta',
        'value-quality-desc': 'Svaki projekt pristupamo s pažnjom na detalje i posvećenošću izvrsnosti. Naš cilj je stvoriti rješenja koja nadmašuju očekivanja.',
        'value-team-title': 'Timski rad',
        'value-team-desc': 'Vjerujemo da najbolji rezultati dolaze kroz suradnju. Radimo zajedno s vama kroz cijeli proces razvoja.',
        'value-innovation-title': 'Inovacija',
        'value-innovation-desc': 'Koristimo najnovije tehnologije i trendove kako bismo stvorili moderne, funkcionalne web stranice.',
        'value-results-title': 'Rezultati',
        'value-results-desc': 'Naš fokus je na mjerljivim rezultatima koji pomažu vašem poslovanju da raste i uspijeva online.',
        'team-title': 'Naš tim',
        'team-subtitle': 'Upoznajte stručnjake koji rade na vašim projektima',
        'team-member-1-name': 'Marko Petrović',
        'team-member-1-role': 'Glavni dizajner',
        'team-member-1-desc': 'Specijalist za korisničko iskustvo s više od 5 godina iskustva u web dizajnu.',
        'team-member-2-name': 'Ana Kovač',
        'team-member-2-role': 'Frontend developer',
        'team-member-2-desc': 'Stručnjakinja za moderne web tehnologije i optimizaciju performansi.',
        'team-member-3-name': 'Petar Novak',
        'team-member-3-role': 'Backend developer',
        'team-member-3-desc': 'Ekspert za server-side tehnologije i sigurnost web aplikacija.',
        'stats-title': 'Naši rezultati',
        'stats-subtitle': 'Brojke koje govore o našem uspjehu',
        'stat-projects': 'Završenih projekata',
        'stat-years': 'Godine iskustva',
        'stat-satisfaction': 'Zadovoljnih klijenata',
        'stat-support': 'Podrška',
        'about-cta-title': 'Spremni za suradnju?',
        'about-cta-desc': 'Kontaktirajte nas danas i počnimo stvarati vašu savršenu web stranicu.',
        'about-cta-button': 'Kontaktirajte nas',
        'about-cta-packages': 'Pogledajte pakete',
        // Footer translations
        'footer-description': 'Transformirajte svoje ideje u digitalnu stvarnost. Profesionalni web dizajn i razvoj na dohvat ruke.',
        'footer-services': 'Usluge',
        'footer-basic-page': 'Osnovna stranica',
        'footer-pro-page': 'Profesionalna stranica',
        'footer-premium-page': 'Premium stranica',
        'footer-consultations': 'Konzultacije',
        'footer-navigation': 'Navigacija',
        'footer-home': 'Početna',
        'footer-packages': 'Paketi',
        'footer-contact': 'Kontakt',
        'footer-faq': 'FAQ',
        'footer-about': 'O nama',
        'footer-follow': 'Pratite nas',
        'footer-all-rights': 'Sva prava pridržana.',
        'footer-privacy': 'Privatnost',
        'footer-terms': 'Uvjeti korištenja',
        // Page titles
        'page-title-home': 'Moderan Web dizajn - Start Smart HR',
        'page-title-about': 'O nama - Start Smart HR',
        'page-title-terms': 'Uvjeti korištenja - Start Smart HR',
        'page-title-privacy': 'Politika privatnosti - Start Smart HR',
        // Privacy page
        'back-to-home': 'Povratak na početnu',
        'privacy-title': 'Politika privatnosti',
        'privacy-subtitle': 'Kako zaštitimo vaše podatke',
        'privacy-section-1-title': '1. Uvod',
        'privacy-section-1-content': 'Start Smart HR („mi", „naš", „nas") poštuje vašu privatnost i obvezuje se zaštititi vaše osobne podatke. Ova politika privatnosti objašnjava kako prikupljamo, koristimo i štitimo vaše informacije kada koristite našu web stranicu.',
        'privacy-section-2-title': '2. Podaci koje prikupljamo',
        'privacy-section-2-intro': '<strong>Važno:</strong> Ne prikupljamo nikakve podatke osim onih koje nam vi izričito pružite kroz kontakt formu.',
        'privacy-section-2-1-title': '2.1 Podaci koje nam vi pružite (samo kroz kontakt formu)',
        'privacy-section-2-1-item1': 'Ime i prezime (samo ako unesete)',
        'privacy-section-2-1-item2': 'Email adresa (samo ako unesete)',
        'privacy-section-2-1-item3': 'Poruka (samo ako unesete)',
        'privacy-section-2-2-title': '2.2 Automatski prikupljeni podaci',
        'privacy-section-2-2-content': '<strong>Ne prikupljamo automatski nikakve podatke.</strong> Naša web stranica ne koristi analitiku, kolačiće za praćenje, niti bilo koji drugi sustav za prikupljanje podataka.',
        'privacy-section-3-title': '3. Kako koristimo vaše podatke',
        'privacy-section-3-intro': '<strong>Koristimo vaše podatke isključivo za:</strong>',
        'privacy-section-3-item1': 'Odgovaranje na vaše upite kroz kontakt formu',
        'privacy-section-3-item2': 'Komunikaciju s vama o našim uslugama',
        'privacy-section-3-item3': 'Pružanje informacija koje ste zatražili',
        'privacy-section-3-not-used': '<strong>Ne koristimo vaše podatke za:</strong>',
        'privacy-section-3-not-item1': 'Marketing (osim ako to izričito ne zatražite)',
        'privacy-section-3-not-item2': 'Prodaju trećim stranama',
        'privacy-section-3-not-item3': 'Praćenje vašeg ponašanja na stranici',
        'privacy-section-4-title': '4. Dijeljenje podataka',
        'privacy-section-4-content': '<strong>Ne dijelimo vaše podatke s nikim.</strong> Vaše osobne podatke ne prodajemo, ne iznajmljujemo niti ih dijelimo s trećim stranama.',
        'privacy-section-4-exception': 'Jedina iznimka je ako to izričito zatražite (npr. preporučivanje drugih usluga).',
        'privacy-section-5-title': '5. Sigurnost podataka',
        'privacy-section-5-content': 'Primjenjujemo odgovarajuće tehničke i organizacijske mjere za zaštitu vaših osobnih podataka protiv neovlaštenog pristupa, promjene, otkrivanja ili uništenja.',
        'privacy-section-6-title': '6. Vaša prava',
        'privacy-section-6-intro': '<strong>Budući da ne prikupljamo podatke osim onih koje nam vi pružite kroz kontakt formu:</strong>',
        'privacy-section-6-item1': 'Možete jednostavno prestati koristiti našu stranicu',
        'privacy-section-6-item2': 'Možete nam reći da ne želite da odgovorimo na vaš upit',
        'privacy-section-6-item3': 'Možete nam reći da ne želite daljnju komunikaciju',
        'privacy-section-6-note': '<strong>Nemamo bazu podataka koju možete "obrisati" jer ne čuvamo vaše podatke.</strong>',
        'privacy-section-7-title': '7. Kolačići (Cookies)',
        'privacy-section-7-content': '<strong>Naša web stranica NE koristi kolačiće.</strong> Ne postavljamo nikakve kolačiće za praćenje, analitiku ili bilo koju drugu svrhu.',
        'privacy-section-7-note': 'Jedini kolačići koji se mogu pojaviti su oni koje postavlja vaš preglednik ili treće stranice (npr. Google Fonts), ali mi ih ne kontroliramo niti koristimo.',
        'privacy-section-8-title': '8. Promjene ove politike',
        'privacy-section-8-content': 'Možemo ažurirati ovu politiku privatnosti s vremena na vrijeme. Sve promjene će biti objavljene na ovoj stranici.',
        'privacy-contact-title': 'Kontaktirajte nas',
        'privacy-contact-intro': 'Ako imate pitanja o ovoj politici privatnosti, kontaktirajte nas:',
        'privacy-contact-email-label': 'Email:',
        'privacy-contact-phone-label': 'Telefon:',
        'privacy-last-updated': 'Zadnje ažuriranje:',
        'privacy-update-date': '25. rujna 2025.',
        // Terms page
        'terms-title': 'Uvjeti korištenja',
        'terms-subtitle': 'Pravila i uvjeti za korištenje naše web stranice',
        'terms-section-1-title': '1. Prihvaćanje uvjeta',
        'terms-section-1-content': 'Korištenjem naše web stranice pristajete na ove uvjete korištenja. Ako se ne slažete s bilo kojim dijelom ovih uvjeta, ne smijete koristiti našu web stranicu.',
        'terms-section-2-title': '2. Opis usluga',
        'terms-section-2-content': 'Start Smart HR pruža usluge web dizajna, razvoja i marketinga. Naše usluge uključuju izradu web stranica, optimizaciju, održavanje i marketing bilo koje vrste.',
        'terms-section-3-title': '3. Korištenje stranice',
        'terms-section-3-intro': '<strong>Možete koristiti našu web stranicu za:</strong>',
        'terms-section-3-item1': 'Pregled naših usluga',
        'terms-section-3-item2': 'Kontaktiranje s nama',
        'terms-section-3-item3': 'Dobivanje informacija o našim paketima',
        'terms-section-3-not-allowed': '<strong>Ne smijete:</strong>',
        'terms-section-3-not-item1': 'Koristiti stranicu za ilegalne svrhe',
        'terms-section-3-not-item2': 'Ometati funkcioniranje stranice',
        'terms-section-3-not-item3': 'Kopirati sadržaj bez dozvole',
        'terms-section-4-title': '4. Intelektualno vlasništvo',
        'terms-section-4-content': 'Sav sadržaj na ovoj stranici, uključujući tekst, grafiku, logotipe, slike i softver, vlasništvo je Start Smart HR-a ili njegovih dobavljača i zaštićen je zakonima o autorskim pravima.',
        'terms-section-5-title': '5. Odricanje odgovornosti',
        'terms-section-5-content': 'Informacije na ovoj web stranici pružaju se "kako jesu" bez jamstava bilo koje vrste.',
        'terms-section-6-title': '6. Ograničenje odgovornosti',
        'terms-section-6-content': 'Start Smart HR neće biti odgovoran za bilo kakvu izravnu, neizravnu, slučajnu, posebnu ili posljedičnu štetu koja proizlazi iz korištenja ovog websitea i/ili njegovih usluga.',
        'terms-section-7-title': '7. Promjene uvjeta',
        'terms-section-7-content': 'Zadržavamo pravo mijenjati ove uvjete korištenja u bilo kojem trenutku. Promjene će stupiti na snagu odmah nakon objavljivanja na ovoj stranici.',
        'terms-section-8-title': '8. Kontakt',
        'terms-section-8-content': 'Ako imate pitanja o ovim uvjetima korištenja, kontaktirajte nas putem informacija navedenih na stranici.',
        'terms-contact-title': 'Kontaktirajte nas',
        'terms-contact-intro': 'Ako imate pitanja o ovim uvjetima korištenja, kontaktirajte nas:',
        'terms-contact-email-label': 'Email:',
        'terms-contact-phone-label': 'Telefon:',
        'terms-last-updated': 'Zadnje ažuriranje:',
        'terms-update-date': '25. rujna 2025.',
        // About page
        'about-subtitle': 'O nama',
        'about-title': 'Naša priča',
        'about-subtitle-text': 'Stvaramo digitalna rješenja koja pokreću vaše poslovanje',
        'about-description': 'Start Smart HR je tim stručnjaka posvećenih stvaranju izvanrednih web stranica koje ne samo da izgledaju odlično, već i donose rezultate. Naša misija je pomoći vašem poslovanju da se istakne u digitalnom svijetu.',
        'values-title': 'Naše vrijednosti',
        'values-subtitle': 'Vjerujemo da svaki uspješan projekt počinje s jasnim vrijednostima i ciljevima.',
        'value-quality-title': 'Kvaliteta',
        'value-quality-desc': 'Svaki projekt je prilika da pokažemo svoju stručnost. Ne štedimo na kvaliteti - od dizajna do koda, sve mora biti savršeno.',
        'value-team-title': 'Timski rad',
        'value-team-desc': 'Vjerujemo u snagu tima. Naš tim različitih stručnjaka radi zajedno kako bi stvorio najbolja moguća rješenja za naše klijente.',
        'value-innovation-title': 'Inovacija',
        'value-innovation-desc': 'Koristimo i stvaramo najnovije tehnologije i trendove kako bismo stvorili web stranice koje su ne samo moderne, već i funkcionalne.',
        'value-results-title': 'Rezultati',
        'value-results-desc': 'Naš cilj nije samo stvoriti lijepu web stranicu, već pomoći vašem poslovanju da raste i uspijeva u digitalnom svijetu.',
        'team-title': 'Naš tim',
        'team-subtitle': 'Upoznajte ljude koji stoje iza naših uspješnih projekata.',
        'team-member-1-name': 'Roko Nevistić',
        'team-member-1-role': 'Glavni developer',
        'team-member-1-desc': 'Stručnjak za frontend, backend i React razvoj web aplikacija.',
        'team-member-2-name': 'Mihael Kovačić',
        'team-member-2-role': 'Glavni dizajner i stručnjak za marketing',
        'team-member-2-desc': 'Preko 3 godine iskustva u marketingu i stručnim odnosima sa klijentima, sa pozadinom u razvoju Web aplikacija.',
        'stats-title': 'Naši rezultati',
        'stats-subtitle': 'Brojke govore same za sebe.',
        'stat-projects': 'Završenih projekata',
        'stat-years': 'Godine iskustva',
        'stat-satisfaction': 'Zadovoljnih klijenata',
        'stat-support': 'Podrška',
        'about-cta-title': 'Spremni za suradnju?',
        'about-cta-desc': 'Kontaktirajte nas danas i počnimo stvarati vašu savršenu web stranicu.',
        'about-cta-button': 'Kontaktirajte nas',
        'about-cta-packages': 'Pogledajte pakete',
        'footer-basic': 'Osnovna stranica',
        'footer-professional': 'Profesionalna stranica',
        'footer-premium': 'Premium stranica',
        'footer-consultation': 'Konzultacije',
        'footer-copyright': '© 2025 Start Smart HR. Sva prava pridržana.',
        // Form validation errors
        'error-name-required': 'Ime je obavezno',
        'error-email-required': 'Email adresa je obavezna',
        'error-message-required': 'Poruka je obavezna',
        'error-form-validation': 'Molimo ispravite greške u formi prije slanja.',
        // CTA section
        'cta-badge': '🔥 AKCIJA!',
        'cta-text': '<strong>50% popusta na sve pakete!</strong> Ograničeno vrijeme!',
        // Package elements
        'discount-banner': '50% POPUST',
        'package-basic-eta': 'ETA: 24-48 sati',
        'package-pro-eta': 'ETA: 72 sata',
        'package-premium-eta': 'ETA: 7 dana',
        'badge-recommended': 'Preporučeno',
        'badge-basic': 'Osnovni',
        'pricing-once': 'jednokratno',
        // Features
        'feature-responsive': 'Responzivan dizajn',
        'feature-fast-loading': 'Brzo učitavanje',
        'feature-seo': 'SEO optimizacija',
        'feature-social': 'Integracija društvenih mreža',
        'feature-advanced-cms': 'Napredni CMS',
        'feature-interactive': 'Interaktivni elementi',
        'feature-blog-gallery': 'Integracija bloga i galerije',
        'feature-security': 'Sigurnosni protokoli',
        'feature-ecommerce': 'E-commerce integracija',
        'feature-multi-user': 'Višekorisnički sustav (Login/Signup)',
        'feature-payment': 'Integracija plaćanja',
        'feature-analytics': 'Napredna analitika',
        // FAQ section
        'faq-title': 'Često postavljana pitanja',
        'faq-subtitle': 'Odgovorili smo na najčešća pitanja o našim uslugama',
        'faq-q1': 'Koliko dugo traje izrada web stranice?',
        'faq-a1': 'Vrijeme izrade ovisi o odabranom paketu: Osnovna stranica (24-48 sati), Profesionalna stranica (72 sata), Premium stranica (7 dana). Uključujemo i vrijeme za revizije i prilagodbe.',
        'faq-q2': 'Mogu li naknadno dodavati sadržaj na stranicu?',
        'faq-a2': 'Da! Profesionalni i Premium paketi uključuju CMS (Content Management System) koji vam omogućava jednostavno dodavanje i uređivanje sadržaja bez tehničkog znanja.',
        'faq-q3': 'Je li stranica optimizirana za mobilne uređaje?',
        'faq-a3': 'Apsolutno! Sve naše stranice su potpuno responzivne i optimizirane za sve uređaje - desktop, tablet i mobilni telefon. Vaša stranica će izgledati savršeno na svim ekranima.',
        'faq-q4': 'Što uključuje SEO optimizacija?',
        'faq-a4': 'SEO optimizacija uključuje: optimizaciju meta tagova, brzinu učitavanja, strukturirane podatke, optimizaciju slika, mobile-friendly dizajn i osnovne SEO postavke za bolje rangiranje u Google pretraživaču.',
        'faq-q5': 'Mogu li promijeniti dizajn nakon što je stranica gotova?',
        'faq-a5': 'Da, možete zatražiti izmjene u dizajnu. Uključujemo 2 besplatne revizije u svaki paket. Dodatne izmjene se naplaćuju prema složenosti promjena.',
        'faq-q6': 'Kako funkcionira hosting i domena?',
        'faq-a6': 'Možemo vam pomoći s odabirom i postavljanjem hostinga i domene. Također nudimo hosting usluge s brzim i sigurnim serverima. Domena se može registrirati u vašem imenu ili našem.',
        'faq-q7': 'Što ako nisam zadovoljan rezultatom?',
        'faq-a7': 'Vaše zadovoljstvo je naš prioritet! Radimo s vama kroz cijeli proces i uključujemo revizije dok ne budete potpuno zadovoljni. Naš cilj je da imate web stranicu koja premašuje vaša očekivanja.',
        'faq-q8': 'Nudite li podršku nakon izrade stranice?',
        'faq-a8': 'Da! Nudimo 30 dana besplatne podrške nakon predaje stranice. Također imamo pakete za održavanje stranice koji uključuju redovite sigurnosne ažuriranja i tehničku podršku.'
      },
      en: {
        // Navigation
        'nav-home': 'Home',
        'nav-packages': 'Packages',
        'nav-contact': 'Contact',
        'nav-faq': 'FAQ',
        'nav-about': 'About',
        // Hero section
        'hero-subtitle': 'Welcome to the future',
        'hero-title-1': 'Professional',
        'hero-title-2': 'Web Design',
        'hero-title-3': 'for your success',
        'hero-description': 'Transform your ideas into digital reality. Let\'s create websites together that attract, convert and grow.',
        'hero-feature-1': 'Fast delivery',
        'hero-feature-2': 'Responsive design',
        'hero-feature-3': 'Modern design',
        'hero-cta': 'Learn more',
        'hero-scroll': 'Scroll',
        // Packages section
        'packages-title': 'Our packages',
        'packages-subtitle': 'Choose the package that suits your needs',
        // Contact section
        'contact-title': 'Contact us',
        'contact-subtitle': 'We are ready to answer all your questions',
        'contact-email': 'Email',
        'contact-phone': 'Phone',
        'contact-location': 'Location',
        'contact-form-name': 'Your name',
        'contact-form-email': 'Your email',
        'contact-form-phone': 'Phone number',
        'contact-form-message': 'Your message',
        'contact-form-robot': 'I am not a robot',
        'contact-form-robot-error': 'Please verify that you are not a robot',
        'contact-form-robot-text': 'I am human',
        'contact-form-gdpr': 'I agree to the <a href="privacy.html" target="_blank" class="form__link" onclick="event.stopPropagation()">Privacy Policy</a> and <a href="terms.html" target="_blank" class="form__link" onclick="event.stopPropagation()">Terms of Use</a> and allow processing of my personal data for the purpose of responding to my inquiry.',
        'contact-form-gdpr-error': 'You must agree to the Privacy Policy and Terms of Use',
        'contact-form-success': 'Message sent successfully! We will respond to you soon.',
        'contact-form-submit': 'Send message',
        // Package cards
        'package-basic-title': 'Basic Website',
        'package-pro-title': 'Professional Website',
        'package-premium-title': 'Premium Website',
        'package-custom-title': 'Custom Project',
        'package-visit-first': 'Visit website',
        'package-visit-second': 'Visit website',
        'package-visit-site': 'Visit website',
        'package-details': 'Details',
        'package-details-hover': 'Show details',
        'package-details-hide': 'Hide details',
        'package-optional-services': 'Additional monthly services',
        'package-optional-services-hide': 'Hide additional services',
        'optional-services-title': 'Additional monthly services:',
        'optional-service-maintenance': 'Site maintenance',
        'optional-service-maintenance-desc': 'Regular security updates, anti-hacking protection, DDoS protection, SSL certificates, backup solutions, performance monitoring, speed optimization, plugin and framework updates, 24/7 technical support',
        'optional-service-content-changes': 'Basic content changes',
        'optional-service-content-changes-desc': 'Text and image changes on your website',
        'pricing-month': '/month',
        'pricing-by-agreement': 'by agreement',
        'feature-bugs-security': '7 days free bug fixes and security updates',
        'details-title': 'Additional details:',
        'details-basic-1': 'Simple navigation',
        'details-basic-2': 'Contact form',
        'details-basic-3': 'Basic animations',
        'details-basic-4': 'Custom layout',
        'details-pro-1': 'Responsive design with advanced animation',
        'details-pro-2': 'CMS integration',
        'details-pro-3': 'Blog and gallery',
        'details-pro-4': 'Fast performance optimization',
        'details-premium-1': 'Custom API integrations',
        'details-premium-2': 'Multi-user system (Login/Signup)',
        'details-premium-3': 'Personalized design',
        'details-premium-4': 'Premium customer support',
        'details-custom-1': 'Individual consultation',
        'details-custom-2': 'Custom integrations',
        'details-custom-3': 'Advanced security',
        'details-custom-4': 'Ongoing support',
        'hero-contact': 'Contact us',
        // Package descriptions
        'package-basic-desc': 'Simplified solution for your first online step. Ideal for personal projects and small businesses.',
        'package-pro-desc': 'Advanced solution with modern CMS and interactive elements, ideal for medium and large companies.',
        'package-premium-desc': 'Complete solution with the latest technologies, tailored to complex requirements and integrations.',
        'package-custom-desc': 'Fully customized solution for large projects with unique requirements. Contact us for details.',
        'package-custom-eta': 'ETA: By agreement',
        'badge-custom': 'Custom',
        'pricing-contact': 'Contact us',
        'pricing-custom': 'for a quote',
        'package-contact-us': 'Contact us',
        'feature-custom-design': 'Fully customized design',
        'feature-custom-functionality': 'Custom functionality',
        'feature-dedicated-support': 'Dedicated support team',
        'feature-scalable': 'Scalable architecture',
        // About page translations
        'about-subtitle': 'About us',
        'about-title': 'Our story',
        'about-subtitle-text': 'Creating digital solutions that drive your business',
        'about-description': 'Start Smart HR is a team of experts dedicated to creating exceptional websites that not only look great but also deliver results. Our mission is to help your business stand out in the digital world.',
        'values-title': 'Our values',
        'values-subtitle': 'The foundation of our work are the values that guide us in every project',
        'value-quality-title': 'Quality',
        'value-quality-desc': 'We approach every project with attention to detail and commitment to excellence. Our goal is to create solutions that exceed expectations.',
        'value-team-title': 'Teamwork',
        'value-team-desc': 'We believe that the best results come through collaboration. We work together with you throughout the entire development process.',
        'value-innovation-title': 'Innovation',
        'value-innovation-desc': 'We use the latest technologies and trends to create modern, functional websites.',
        'value-results-title': 'Results',
        'value-results-desc': 'Our focus is on measurable results that help your business grow and succeed online.',
        'team-title': 'Our team',
        'team-subtitle': 'Meet the experts who work on your projects',
        'team-member-1-name': 'Roko Nevistić',
        'team-member-1-role': 'Senior developer',
        'team-member-1-desc': 'Expert in frontend, backend and React web application development.',
        'team-member-2-name': 'Mihael Kovačić',
        'team-member-2-role': 'Senior designer and marketing expert',
        'team-member-2-desc': 'With over 3 years of experience in marketing and professional client relations with a background in web application development.',
        'stats-title': 'Our results',
        'stats-subtitle': 'Numbers that speak about our success',
        'stat-projects': 'Completed projects',
        'stat-years': 'Years of experience',
        'stat-satisfaction': 'Satisfied clients',
        'stat-support': 'Support',
        'about-cta-title': 'Ready to collaborate?',
        'about-cta-desc': 'Contact us today and let\'s start creating your perfect website.',
        'about-cta-button': 'Contact us',
        'about-cta-packages': 'View packages',
        // Footer translations
        'footer-description': 'Transform your ideas into digital reality. Professional web design and development at your fingertips.',
        'footer-services': 'Services',
        'footer-basic-page': 'Basic Website',
        'footer-pro-page': 'Professional Website',
        'footer-premium-page': 'Premium Website',
        'footer-consultations': 'Consultations',
        'footer-navigation': 'Navigation',
        'footer-home': 'Home',
        'footer-packages': 'Packages',
        'footer-contact': 'Contact',
        'footer-faq': 'FAQ',
        'footer-about': 'About',
        'footer-follow': 'Follow us',
        'footer-all-rights': 'All rights reserved.',
        'footer-privacy': 'Privacy',
        'footer-terms': 'Terms of Service',
        // Page titles
        'page-title-home': 'Modern Web Design - Start Smart HR',
        'page-title-about': 'About us - Start Smart HR',
        'page-title-terms': 'Terms of use - Start Smart HR',
        'page-title-privacy': 'Privacy policy - Start Smart HR',
        // Privacy page
        'back-to-home': 'Back to home',
        'privacy-title': 'Privacy Policy',
        'privacy-subtitle': 'How we protect your data',
        'privacy-section-1-title': '1. Introduction',
        'privacy-section-1-content': 'Start Smart HR ("we", "our", "us") respects your privacy and is committed to protecting your personal data. This privacy policy explains how we collect, use and protect your information when you use our website.',
        'privacy-section-2-title': '2. Data we collect',
        'privacy-section-2-intro': '<strong>Important:</strong> We do not collect any data except what you explicitly provide to us through the contact form.',
        'privacy-section-2-1-title': '2.1 Data you provide to us (only through contact form)',
        'privacy-section-2-1-item1': 'Name and surname (only if you enter it)',
        'privacy-section-2-1-item2': 'Email address (only if you enter it)',
        'privacy-section-2-1-item3': 'Message (only if you enter it)',
        'privacy-section-2-2-title': '2.2 Automatically collected data',
        'privacy-section-2-2-content': '<strong>We do not automatically collect any data.</strong> Our website does not use analytics, tracking cookies, or any other data collection system.',
        'privacy-section-3-title': '3. How we use your data',
        'privacy-section-3-intro': '<strong>We use your data exclusively for:</strong>',
        'privacy-section-3-item1': 'Responding to your inquiries through the contact form',
        'privacy-section-3-item2': 'Communicating with you about our services',
        'privacy-section-3-item3': 'Providing information you have requested',
        'privacy-section-3-not-used': '<strong>We do not use your data for:</strong>',
        'privacy-section-3-not-item1': 'Marketing (unless you explicitly request it)',
        'privacy-section-3-not-item2': 'Selling to third parties',
        'privacy-section-3-not-item3': 'Tracking your behavior on the site',
        'privacy-section-4-title': '4. Data sharing',
        'privacy-section-4-content': '<strong>We do not share your data with anyone.</strong> We do not sell, rent or share your personal data with third parties.',
        'privacy-section-4-exception': 'The only exception is if you explicitly request it (e.g. recommending other services).',
        'privacy-section-5-title': '5. Data security',
        'privacy-section-5-content': 'We implement appropriate technical and organizational measures to protect your personal data against unauthorized access, alteration, disclosure or destruction.',
        'privacy-section-6-title': '6. Your rights',
        'privacy-section-6-intro': '<strong>Since we do not collect data except what you provide to us through the contact form:</strong>',
        'privacy-section-6-item1': 'You can simply stop using our site',
        'privacy-section-6-item2': 'You can tell us you do not want us to respond to your inquiry',
        'privacy-section-6-item3': 'You can tell us you do not want further communication',
        'privacy-section-6-note': '<strong>We do not have a database that you can "delete" because we do not store your data.</strong>',
        'privacy-section-7-title': '7. Cookies',
        'privacy-section-7-content': '<strong>Our website does NOT use cookies.</strong> We do not set any cookies for tracking, analytics or any other purpose.',
        'privacy-section-7-note': 'The only cookies that may appear are those set by your browser or third parties (e.g. Google Fonts), but we do not control or use them.',
        'privacy-section-8-title': '8. Changes to this policy',
        'privacy-section-8-content': 'We may update this privacy policy from time to time. All changes will be published on this page.',
        'privacy-contact-title': 'Contact us',
        'privacy-contact-intro': 'If you have questions about this privacy policy, contact us:',
        'privacy-contact-email-label': 'Email:',
        'privacy-contact-phone-label': 'Phone:',
        'privacy-last-updated': 'Last updated:',
        'privacy-update-date': 'September 25, 2025.',
        // Terms page
        'terms-title': 'Terms of Use',
        'terms-subtitle': 'Rules and conditions for using our website',
        'terms-section-1-title': '1. Acceptance of terms',
        'terms-section-1-content': 'By using our website, you agree to these terms of use. If you do not agree to any part of these terms, you may not use our website.',
        'terms-section-2-title': '2. Service description',
        'terms-section-2-content': 'Start Smart HR provides web design, development services and marketing. Our services include creating websites, optimization, maintenance and marketing of any kind.',
        'terms-section-3-title': '3. Use of the site',
        'terms-section-3-intro': '<strong>You may use our website for:</strong>',
        'terms-section-3-item1': 'Viewing our services',
        'terms-section-3-item2': 'Contacting us',
        'terms-section-3-item3': 'Getting information about our packages',
        'terms-section-3-not-allowed': '<strong>You may not:</strong>',
        'terms-section-3-not-item1': 'Use the site for illegal purposes',
        'terms-section-3-not-item2': 'Disrupt the functioning of the site',
        'terms-section-3-not-item3': 'Copy content without permission',
        'terms-section-4-title': '4. Intellectual property',
        'terms-section-4-content': 'All content on this site, including text, graphics, logos, images and software, is the property of Start Smart HR or its suppliers and is protected by copyright laws.',
        'terms-section-5-title': '5. Disclaimer',
        'terms-section-5-content': 'Information on this website is provided "as is" without warranties of any kind.',
        'terms-section-6-title': '6. Limitation of liability',
        'terms-section-6-content': 'Start Smart HR will not be liable for any direct, indirect, incidental, special or consequential damages arising from the use of this website and/or its services.',
        'terms-section-7-title': '7. Changes to terms',
        'terms-section-7-content': 'We reserve the right to change these terms of use at any time. Changes will take effect immediately after publication on this page.',
        'terms-section-8-title': '8. Contact',
        'terms-section-8-content': 'If you have questions about these terms of use, contact us using the information provided on the page.',
        'terms-contact-title': 'Contact us',
        'terms-contact-intro': 'If you have questions about these terms of use, contact us:',
        'terms-contact-email-label': 'Email:',
        'terms-contact-phone-label': 'Phone:',
        'terms-last-updated': 'Last updated:',
        'terms-update-date': 'September 25, 2025.',
        // About page
        'about-subtitle': 'About us',
        'about-title': 'Our story',
        'about-subtitle-text': 'Creating digital solutions that drive your business',
        'about-description': 'Start Smart HR is a team of experts dedicated to creating exceptional websites that not only look great but also deliver results. Our mission is to help your business stand out in the digital world.',
        'values-title': 'Our values',
        'values-subtitle': 'We believe that every successful project starts with clear values and goals.',
        'value-quality-title': 'Quality',
        'value-quality-desc': 'Every project is an opportunity to showcase our expertise. We don\'t compromise on quality - from design to code, everything must be perfect.',
        'value-team-title': 'Teamwork',
        'value-team-desc': 'We believe in the power of teamwork. Our team of diverse experts works together to create the best possible solutions for our clients.',
        'value-innovation-title': 'Innovation',
        'value-innovation-desc': 'We use the latest technologies and trends to create websites that are not only modern but also functional.',
        'value-results-title': 'Results',
        'value-results-desc': 'Our goal is not just to create a beautiful website, but to help your business grow and succeed in the digital world.',
        'team-title': 'Our team',
        'team-subtitle': 'Meet the people behind our successful projects.',
'team-member-1-name': 'Roko Nevistić',
        'team-member-1-role': 'Senior developer',
        'team-member-1-desc': 'Expert in frontend, backend and React web application development.',
        'team-member-2-name': 'Mihael Kovačić',
        'team-member-2-role': 'Senior designer and marketing expert',
        'team-member-2-desc': 'With over 3 years of experience in marketing and professional client relations with a background in web application development.',
        'stats-title': 'Our results',
        'stats-subtitle': 'The numbers speak for themselves.',
        'stat-projects': 'Completed projects',
        'stat-years': 'Years of experience',
        'stat-satisfaction': 'Satisfied clients',
        'stat-support': 'Support',
        'about-cta-title': 'Ready to collaborate?',
        'about-cta-desc': 'Contact us today and let\'s start creating your perfect website.',
        'about-cta-button': 'Contact us',
        'about-cta-packages': 'View packages',
        'footer-basic': 'Basic website',
        'footer-professional': 'Professional website',
        'footer-premium': 'Premium website',
        'footer-consultation': 'Consultations',
        'footer-copyright': '© 2025 Start Smart HR. All rights reserved.',
        // Form validation errors
        'error-name-required': 'Name is required',
        'error-email-required': 'Email address is required',
        'error-message-required': 'Message is required',
        'error-form-validation': 'Please correct errors in the form before submitting.',
        // CTA section
        'cta-badge': '🔥 SALE!',
        'cta-text': '<strong>50% discount on all packages!</strong> Limited time!',
        // Package elements
        'discount-banner': '50% DISCOUNT',
        'package-basic-eta': 'ETA: 24-48 hours',
        'package-pro-eta': 'ETA: 72 hours',
        'package-premium-eta': 'ETA: 7 days',
        'badge-recommended': 'Recommended',
        'badge-basic': 'Basic',
        'pricing-once': 'one-time',
        // Features
        'feature-responsive': 'Responsive design',
        'feature-fast-loading': 'Fast loading',
        'feature-seo': 'SEO optimization',
        'feature-social': 'Social media integration',
        'feature-advanced-cms': 'Advanced CMS',
        'feature-interactive': 'Interactive elements',
        'feature-blog-gallery': 'Blog and gallery integration',
        'feature-security': 'Security protocols',
        'feature-ecommerce': 'E-commerce integration',
        'feature-multi-user': 'Multi-user system (Login/Signup)',
        'feature-payment': 'Payment integration',
        'feature-analytics': 'Advanced analytics',
        // FAQ section
        'faq-title': 'Frequently Asked Questions',
        'faq-subtitle': 'We\'ve answered the most common questions about our services',
        'faq-q1': 'How long does it take to create a website?',
        'faq-a1': 'The development time depends on the chosen package: Basic Website (24-48 hours), Professional Website (72 hours), Premium Website (7 days). We also include time for revisions and adjustments.',
        'faq-q2': 'Can I add content to the website later?',
        'faq-a2': 'Yes! Professional and Premium packages include a CMS (Content Management System) that allows you to easily add and edit content without technical knowledge.',
        'faq-q3': 'Is the website optimized for mobile devices?',
        'faq-a3': 'Absolutely! All our websites are fully responsive and optimized for all devices - desktop, tablet and mobile phone. Your website will look perfect on all screens.',
        'faq-q4': 'What does SEO optimization include?',
        'faq-a4': 'SEO optimization includes: meta tag optimization, loading speed, structured data, image optimization, mobile-friendly design and basic SEO settings for better ranking in Google search.',
        'faq-q5': 'Can I change the design after the website is finished?',
        'faq-a5': 'Yes, you can request design changes. We include 2 free revisions in each package. Additional changes are charged according to the complexity of the changes.',
        'faq-q6': 'How does hosting and domain work?',
        'faq-a6': 'We can help you choose and set up hosting and domain. We also offer hosting services with fast and secure servers. The domain can be registered in your name or ours.',
        'faq-q7': 'What if I\'m not satisfied with the result?',
        'faq-a7': 'Your satisfaction is our priority! We work with you throughout the entire process and include revisions until you are completely satisfied. Our goal is for you to have a website that exceeds your expectations.',
        'faq-q8': 'Do you offer support after website creation?',
        'faq-a8': 'Yes! We offer 30 days of free support after website delivery. We also have website maintenance packages that include regular security updates and technical support.'
      }
    };

    // Apply translations
    Object.keys(translations[lang]).forEach(key => {
      const elements = document.querySelectorAll(`[data-translate="${key}"]`);
      elements.forEach(element => {
        // Use innerHTML for elements that need HTML rendering
        const htmlKeys = ['cta-text', 'contact-form-gdpr', 'privacy-section-2-intro', 'privacy-section-2-2-content', 'privacy-section-3-intro', 'privacy-section-3-not-used', 'privacy-section-4-content', 'privacy-section-6-intro', 'privacy-section-6-note', 'privacy-section-7-content', 'privacy-section-7-note', 'terms-section-3-intro', 'terms-section-3-not-allowed'];
        
        if (htmlKeys.includes(key)) {
          element.innerHTML = translations[lang][key];
        } else {
          element.textContent = translations[lang][key];
        }
      });
    });

    // Translate page title
    const titleElement = document.querySelector('title[data-translate]');
    if (titleElement) {
      const titleKey = titleElement.getAttribute('data-translate');
      if (translations[lang][titleKey]) {
        document.title = translations[lang][titleKey];
      }
    }

    // Apply hover translations
    this.applyHoverTranslations(lang, translations);
    
    // Handle data-lang-hr and data-lang-en elements (for dynamic content from database)
    this.applyDynamicLanguage(lang);
  }

  applyDynamicLanguage(lang) {
    // Show/hide elements based on language
    const hrElements = document.querySelectorAll('[data-lang-hr]');
    const enElements = document.querySelectorAll('[data-lang-en]');
    
    hrElements.forEach(el => {
      el.style.display = lang === 'hr' ? '' : 'none';
    });
    
    enElements.forEach(el => {
      el.style.display = lang === 'en' ? '' : 'none';
    });
  }

  applyHoverTranslations(lang, translations) {
    const hoverElements = document.querySelectorAll('[data-translate-hover]');
    hoverElements.forEach(element => {
      const hoverKey = element.getAttribute('data-translate-hover');
      const hoverText = translations[lang][hoverKey];
      
      if (hoverText) {
        element.setAttribute('title', hoverText);
        element.addEventListener('mouseenter', () => {
          const textElement = element.querySelector('.btn__text');
          if (textElement) {
            // Check if details are currently open
            const detailsId = element.getAttribute('onclick').match(/toggleDetails\('([^']+)'\)/)?.[1];
            if (detailsId) {
              const detailsElement = document.getElementById(detailsId);
              const isDetailsOpen = detailsElement && detailsElement.classList.contains('package-card__details--active');
              
              if (isDetailsOpen) {
                textElement.textContent = translations[lang]['package-details-hide'];
              } else {
                textElement.textContent = hoverText;
              }
            }
          }
        });
        
        element.addEventListener('mouseleave', () => {
          const textElement = element.querySelector('.btn__text');
          if (textElement) {
            // Check if details are currently open
            const detailsId = element.getAttribute('onclick').match(/toggleDetails\('([^']+)'\)/)?.[1];
            if (detailsId) {
              const detailsElement = document.getElementById(detailsId);
              const isDetailsOpen = detailsElement && detailsElement.classList.contains('package-card__details--active');
              
              if (isDetailsOpen) {
                textElement.textContent = translations[lang]['package-details-hide'];
              } else {
                textElement.textContent = translations[lang]['package-details'];
              }
            }
          }
        });
      }
    });
  }
}

// Toggle details function (keeping for compatibility)
function toggleDetails(id) {
  const element = document.getElementById(id);
  if (element) {
    const isActive = element.classList.contains('package-card__details--active');
    element.classList.toggle('package-card__details--active');
    
    // Find the corresponding button
    const button = document.querySelector(`[onclick="toggleDetails('${id}')"]`);
    if (button) {
      const textElement = button.querySelector('.btn__text');
      if (textElement) {
        const currentLang = document.documentElement.getAttribute('data-lang') || 'hr';
        const translations = {
          hr: {
            'package-details': 'Detalji',
            'package-details-hide': 'Sakrij detalje'
          },
          en: {
            'package-details': 'Details',
            'package-details-hide': 'Hide details'
          }
        };
        
        if (isActive) {
          // Details are being closed, show "Details"
          textElement.textContent = translations[currentLang]['package-details'];
        } else {
          // Details are being opened, show "Hide details"
          textElement.textContent = translations[currentLang]['package-details-hide'];
        }
      }
    }
  }
}

function toggleOptional(id) {
  const element = document.getElementById(id);
  if (element) {
    const isActive = element.classList.contains('package-card__optional--active');
    element.classList.toggle('package-card__optional--active');
    
    // Find the corresponding button
    const button = document.querySelector(`[onclick="toggleOptional('${id}')"]`);
    if (button) {
      const textElement = button.querySelector('.btn__text');
      if (textElement) {
        const currentLang = document.documentElement.getAttribute('data-lang') || 'hr';
        const translations = {
          hr: {
            'package-optional-services': 'Dodatne mjesečne usluge',
            'package-optional-services-hide': 'Sakrij dodatne usluge'
          },
          en: {
            'package-optional-services': 'Additional monthly services',
            'package-optional-services-hide': 'Hide additional services'
          }
        };
        
        if (isActive) {
          textElement.textContent = translations[currentLang]['package-optional-services'];
        } else {
          textElement.textContent = translations[currentLang]['package-optional-services-hide'];
        }
      }
    }
  }
}

// Initialize the app when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
  new PortfolioApp();
  
  // Set dynamic copyright year
  const currentYearElement = document.getElementById('current-year');
  if (currentYearElement) {
    currentYearElement.textContent = new Date().getFullYear();
  }
  
  // Check emoji support and show fallbacks if needed
  checkEmojiSupport();
  
});

// Function to check if emojis are supported and show fallbacks if not
function checkEmojiSupport() {
  const flagEmojis = document.querySelectorAll('.flag-emoji');
  
  // Detect Edge browser
  const isEdge = /Edg/.test(navigator.userAgent) || /Edge/.test(navigator.userAgent);
  const isOldEdge = /Edge/.test(navigator.userAgent) && !/Edg/.test(navigator.userAgent);
  
  flagEmojis.forEach(emoji => {
    const fallback = emoji.nextElementSibling;
    if (fallback && fallback.classList.contains('flag-fallback')) {
      // Force fallback in Edge browsers
      if (isEdge || isOldEdge) {
        emoji.style.display = 'none';
        fallback.style.display = 'block';
        fallback.style.fontWeight = '700';
        
        // Set color based on theme
        const isDarkMode = document.documentElement.getAttribute('data-theme') === 'dark';
        fallback.style.color = isDarkMode ? 'var(--color-white)' : 'var(--color-neutral-900)';
        return;
      }
      
      // Test if emoji renders properly in other browsers
      const testCanvas = document.createElement('canvas');
      const testContext = testCanvas.getContext('2d');
      testContext.font = '20px "Segoe UI Emoji", "Apple Color Emoji", "Noto Color Emoji"';
      testContext.fillText(emoji.textContent, 0, 20);
      const imageData = testContext.getImageData(0, 0, 20, 20);
      const hasColor = Array.from(imageData.data).some((value, index) => 
        index % 4 !== 3 && value !== 0
      );
      
      // If emoji doesn't render properly, show fallback
      if (!hasColor || emoji.textContent.length > 2) {
        emoji.style.display = 'none';
        fallback.style.display = 'block';
        fallback.style.fontWeight = '700';
        
        // Set color based on theme
        const isDarkMode = document.documentElement.getAttribute('data-theme') === 'dark';
        fallback.style.color = isDarkMode ? 'var(--color-white)' : 'var(--color-neutral-900)';
      } else {
        emoji.style.display = 'block';
        fallback.style.display = 'none';
      }
    }
  });
}
