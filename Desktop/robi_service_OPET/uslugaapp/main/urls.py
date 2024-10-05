# myproject/urls.py

from django.contrib import admin
from django.urls import path
from django.contrib.auth import views as auth_views
from main import views
from django.conf import settings
from django.conf.urls.static import static

urlpatterns = [
    path('admin/', admin.site.urls),
    path('', views.home, name='home'),
    path('signup/', views.signup, name='signup'),
    path('login/', auth_views.LoginView.as_view(template_name='main/login.html'), name='login'),
    path('logout/', auth_views.LogoutView.as_view(), name='logout'),
    path('services/', views.services_list, name='services_list'),  # This is the correct pattern
    path('services/<int:service_id>/', views.service_detail, name='service_detail'),
    path('subservices/<int:subservice_id>/profiles/', views.profiles, name='profiles'),
    path('profiles/<int:profile_id>/', views.profile_detail, name='profile_detail'),
    path('profiles/edit/', views.edit_profile, name='edit_profile'),
    path('rate/<int:ratee_id>/<int:subservice_id>/', views.add_rating, name='add_rating'),
    path('about/', views.about, name='about'),
    path('contact/', views.contact, name='contact'),
    path('companies/', views.companies, name='companies'),  # Companies page
    path('companies/create/', views.create_company, name='create_company'),  # Create company job post
]

if settings.DEBUG:
    urlpatterns += static(settings.MEDIA_URL, document_root=settings.MEDIA_ROOT)