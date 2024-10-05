# main/views.py

from django.shortcuts import render, redirect, get_object_or_404
from django.contrib.auth import login
from django.contrib.auth.decorators import login_required
from .forms import SignUpForm, ProfileForm, RatingForm, CompanyForm
from .models import Service, SubService, Profile, Rating, Company, PaidAd
from django.contrib.auth.models import User
from django.db.models import Avg, Q, Count
from django.contrib import messages
from django.core.mail import send_mail
from django.conf import settings
from django.core.paginator import Paginator
import logging

logger = logging.getLogger(__name__)

def home(request):
    services = Service.objects.all()[:6]  # Display only 6 services
    paid_ads = PaidAd.objects.all()  # Assuming PaidAd model exists
    context = {
        'services': services,
        'paid_ads': paid_ads,
    }
    return render(request, 'main/home.html', context)

def signup(request):
    if request.method == 'POST':
        form = SignUpForm(request.POST)
        if form.is_valid():
            user = form.save()
            login(request, user)
            messages.success(request, "Registration successful. Welcome!")
            return redirect('services')
        else:
            messages.error(request, "Please correct the errors below.")
    else:
        form = SignUpForm()
    return render(request, 'main/signup.html', {'form': form})

def services(request):
    services = Service.objects.all()
    return render(request, 'main/services.html', {'services': services})

def subservices(request, service_id):
    service = get_object_or_404(Service, id=service_id)
    subservices = service.subservices.all()
    return render(request, 'main/subservices.html', {'service': service, 'subservices': subservices})

def profiles(request, subservice_id):
    subservice = get_object_or_404(Subservice, id=subservice_id)
    
    # Filter profiles by the subservice and annotate each with the average rating specific to this subservice
    profiles = Profile.objects.filter(subservices=subservice).annotate(
        avg_rating=Avg(
            'user__ratings_received__rating',
            filter=Q(user__ratings_received__subservice=subservice)
        )
    )
    
    return render(request, 'main/profiles.html', {
        'subservice': subservice,
        'profiles': profiles
    })

@login_required
def profile_detail(request, profile_id):
    profile = get_object_or_404(Profile, id=profile_id)
    
    # Fetch all ratings for the user's subservices, ordered by newest first
    ratings_list = Rating.objects.filter(ratee=profile.user).order_by('-created_at')

    # Set up pagination, 5 ratings per page
    paginator = Paginator(ratings_list, 5)
    page_number = request.GET.get('page')
    ratings = paginator.get_page(page_number)

    # Calculate total average rating
    total_average_rating = ratings_list.aggregate(average=Avg('rating'))['average']

    # Count the total number of reviews
    total_review_count = ratings_list.count()

    # Handle new rating submission
    if request.method == 'POST':
        form = RatingForm(request.POST)
        if form.is_valid():
            rating = form.save(commit=False)
            rating.rater = request.user
            rating.ratee = profile.user
            # Remove the following lines related to subservice
            # if 'subservice' in request.POST and request.POST['subservice']:
            #     rating.subservice = SubService.objects.get(id=request.POST['subservice'])
            rating.save()
            messages.success(request, "Your rating has been submitted.")
            return redirect('profile_detail', profile_id=profile.id)
        else:
            messages.error(request, "Please correct the errors below.")
    else:
        form = RatingForm()

    context = {
        'profile': profile,
        'ratings': ratings,  # Paginated ratings
        'total_average_rating': total_average_rating,
        'total_review_count': total_review_count,
        'form': form,
    }

    return render(request, 'main/profile_detail.html', context)

@login_required
def edit_profile(request):
    profile = request.user.profile
    services = Service.objects.all().order_by('name')

    # Create a dictionary mapping service IDs to their subservices
    subservices_by_service = {}
    for service in services:
        subservices = service.subservices.all().order_by('name')
        subservices_by_service[service.id] = [{"id": sub.id, "name": sub.name} for sub in subservices]

    if request.method == 'POST':
        form = RatingForm(request.POST)
    if form.is_valid():
        rating = form.save(commit=False)
        rating.rater = request.user
        rating.ratee = profile.user
        if 'subservice' in request.POST and request.POST['subservice']:
            rating.subservice = SubService.objects.get(id=request.POST['subservice'])
        rating.save()
        messages.success(request, "Your rating has been submitted.")
        return redirect('profile_detail', profile_id=profile.id)
    else:
        messages.error(request, "Please correct the errors below.")

    context = {
        'form': form,
        'services': services,
        'subservices_by_service': subservices_by_service,
        'profile': profile,
    }
    return render(request, 'main/edit_profile.html', context)

@login_required
def add_rating(request, ratee_id, subservice_id):
    ratee = get_object_or_404(User, id=ratee_id)
    subservice = get_object_or_404(SubService, id=subservice_id)
    
    # Prevent users from rating themselves
    if request.user == ratee:
        messages.error(request, "You cannot rate yourself.")
        return redirect('profile_detail', profile_id=ratee.profile.id)
    
    # Check if the user has already rated this ratee for this subservice
    existing_rating = Rating.objects.filter(
        rater=request.user,
        ratee=ratee,
        subservice=subservice
    ).first()
    
    if existing_rating:
        messages.error(request, "You have already rated this professional for this service.")
        return redirect('profile_detail', profile_id=ratee.profile.id)
    
    if request.method == 'POST':
        form = RatingForm(request.POST)
        if form.is_valid():
            rating = form.save(commit=False)
            rating.rater = request.user
            rating.ratee = ratee
            rating.subservice = subservice
            rating.save()
            messages.success(request, "Your rating has been submitted.")
            return redirect('profile_detail', profile_id=ratee.profile.id)
        else:
            messages.error(request, "Please correct the errors below.")
    else:
        form = RatingForm()
    return render(request, 'main/add_rating.html', {'form': form, 'ratee': ratee, 'subservice': subservice})

def about(request):
    return render(request, 'main/about.html')

def contact(request):
    if request.method == 'POST':
        name = request.POST.get('name')
        email = request.POST.get('email')
        message = request.POST.get('message')
        subject = f"Contact Form Submission from {name}"
        full_message = f"Name: {name}\nEmail: {email}\n\nMessage:\n{message}"
        
        # Send email (ensure EMAIL_BACKEND is configured in settings.py)
        send_mail(
            subject,
            full_message,
            settings.DEFAULT_FROM_EMAIL,
            [settings.ADMIN_EMAIL],
            fail_silently=False,
        )
        messages.success(request, "Your message has been sent successfully.")
        return redirect('contact')
    return render(request, 'main/contact.html')

# Display all companies and their projects
def companies(request):
    # Fetch all business profiles that have posted job ads
    business_profiles = Profile.objects.filter(user_type='business')

    return render(request, 'main/companies.html', {'business_profiles': business_profiles})

# Allow a company to post a job
@login_required
def create_company(request):
    if request.user.profile.user_type != 'business':
        # If not a business owner, restrict access
        return redirect('companies')
    
    if request.method == 'POST':
        form = CompanyForm(request.POST)
        if form.is_valid():
            company = form.save(commit=False)
            company.posted_by = request.user
            company.save()
            return redirect('companies')
    else:
        form = CompanyForm()
    
    return render(request, 'main/create_company.html', {'form': form})

@login_required
def create_service_post(request):
    if request.user.profile.user_type != 'worker':
        # If not a private worker, restrict access
        return redirect('services')
    
    if request.method == 'POST':
        form = ServiceForm(request.POST)
        if form.is_valid():
            service = form.save(commit=False)
            service.posted_by = request.user
            service.save()
            return redirect('services')
    else:
        form = ServiceForm()
    
    return render(request, 'main/create_service.html', {'form': form})

def services(request):
    # Fetch all private profiles that are looking for jobs
    private_profiles = Profile.objects.filter(user_type='private')

    return render(request, 'main/services.html', {'private_profiles': private_profiles})

def services_list(request):
    services = Service.objects.all()  # Fetch all services
    return render(request, 'main/services_list.html', {'services': services})


def service_detail(request, service_id):
    service = get_object_or_404(Service, id=service_id)
    subservices = SubService.objects.filter(service=service)

    # Apply filtering logic based on the subservice ID if selected
    subservice_id = request.GET.get('subservice')
    if subservice_id:
        private_profiles = Profile.objects.filter(subservices__id=subservice_id).distinct()
    else:
        # Use distinct() to avoid duplication of profiles
        private_profiles = Profile.objects.filter(subservices__service=service).distinct()

    # Calculate overall rating for each profile
    profiles_with_ratings = []
    for profile in private_profiles:
        # Calculate average rating for the profile
        average_rating = Rating.objects.filter(ratee=profile.user).aggregate(Avg('rating'))['rating__avg'] or 0
        profiles_with_ratings.append({
            'profile': profile,
            'average_rating': average_rating
        })

    return render(request, 'main/services.html', {
        'service': service,
        'private_profiles': profiles_with_ratings,
        'subservices': subservices
    })