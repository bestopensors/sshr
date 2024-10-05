# main/models.py

from django.db import models
from django.contrib.auth.models import User
from django.core.validators import MinValueValidator, MaxValueValidator

class Service(models.Model):
    name = models.CharField(max_length=100)
    description = models.TextField(blank=True)
    image = models.ImageField(upload_to='service_images/', null=True, blank=True)  # New field for service images
    
    def __str__(self):
        return self.name

class SubService(models.Model):
    service = models.ForeignKey(Service, related_name='subservices', on_delete=models.CASCADE)
    name = models.CharField(max_length=100)
    description = models.TextField(blank=True)
    image = models.ImageField(upload_to='subservice_images/', null=True, blank=True)  # New field for subservice images
    
    def __str__(self):
        return f"{self.service.name} - {self.name}"

class Profile(models.Model):
    USER_TYPES = [
        ('private', 'Private (Looking for Job)'),
        ('business', 'Business (Looking for Workers)')
    ]
    
    user = models.OneToOneField(User, on_delete=models.CASCADE)
    user_type = models.CharField(max_length=10, choices=USER_TYPES, default='private')  # New field
    quantity = models.PositiveIntegerField(null=True, blank=True)  # Quantity field for business users
    subservices = models.ManyToManyField(SubService, related_name='profiles', blank=True)
    image = models.ImageField(upload_to='profile_images/', null=True, blank=True)
    bio = models.TextField(blank=True)
    phone = models.CharField(max_length=20, blank=True)
    email = models.EmailField(blank=True)
    public_phone = models.BooleanField(default=False)
    public_email = models.BooleanField(default=False)

    def __str__(self):
        return self.user.username

class Rating(models.Model):
    rater = models.ForeignKey(User, on_delete=models.CASCADE, related_name='ratings_given')
    ratee = models.ForeignKey(User, on_delete=models.CASCADE, related_name='ratings_received')
    rating = models.FloatField()
    comment = models.TextField()
    created_at = models.DateTimeField(auto_now_add=True)

class Company(models.Model):
    name = models.CharField(max_length=255)
    description = models.TextField()
    location = models.CharField(max_length=255)
    number_of_workers_needed = models.IntegerField()
    posted_by = models.ForeignKey(User, on_delete=models.CASCADE)  # A company user account
    created_at = models.DateTimeField(auto_now_add=True)

    def __str__(self):
        return self.name

class PaidAd(models.Model):
    name = models.CharField(max_length=100)
    description = models.TextField()
    image = models.ImageField(upload_to='ads/', blank=True, null=True)
    service = models.ForeignKey(Service, on_delete=models.CASCADE)

    def __str__(self):
        return self.name