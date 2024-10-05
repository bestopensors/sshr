# main/forms.py

from django import forms
from django.contrib.auth.forms import UserCreationForm
from django.contrib.auth.models import User
from .models import Profile, Rating, SubService, Service, Company

class SignUpForm(UserCreationForm):
    email = forms.EmailField(required=True)
    
    class Meta:
        model = User
        fields = ('username', 'email', 'password1', 'password2')
    
    def clean_email(self):
        email = self.cleaned_data.get('email')
        if User.objects.filter(email=email).exists():
            raise forms.ValidationError("This email address is already in use.")
        return email

class ProfileForm(forms.ModelForm):
    class Meta:
        model = Profile
        # Exclude 'subservices' since we're handling it separately
        exclude = ['user', 'subservices']
        # Specify widgets for included fields
        widgets = {
            'user_type': forms.RadioSelect(),
            'image': forms.ClearableFileInput(attrs={'class': 'form-control-file'}),
            'bio': forms.Textarea(attrs={'class': 'form-control', 'rows': 4}),
            'phone': forms.TextInput(attrs={'class': 'form-control'}),
            'email': forms.EmailInput(attrs={'class': 'form-control'}),
            'public_phone': forms.CheckboxInput(attrs={'class': 'form-check-input'}),
            'public_email': forms.CheckboxInput(attrs={'class': 'form-check-input'}),
        }

    def __init__(self, *args, **kwargs):
        super(ProfileForm, self).__init__(*args, **kwargs)
        # Since 'subservices' is excluded, remove any initialization related to it
        # If you have custom logic related to 'subservices', ensure it's removed or handled appropriately

class RatingForm(forms.ModelForm):
    class Meta:
        model = Rating
        fields = ('rating', 'comment')
        widgets = {
            'rating': forms.HiddenInput(),  # Handled via JS
            'comment': forms.Textarea(attrs={'class': 'form-control', 'rows': 3, 'placeholder': 'Leave a comment...'}),
        }
        labels = {
            'comment': '',
        }

class ServiceForm(forms.ModelForm):
    class Meta:
        model = Service
        fields = ['name', 'description', 'image']  # Include the image field

class SubServiceForm(forms.ModelForm):
    class Meta:
        model = SubService
        fields = ['service', 'name', 'description', 'image']  # Include the image field

class CompanyForm(forms.ModelForm):
    class Meta:
        model = Company
        fields = ['name', 'description', 'location', 'number_of_workers_needed']
        widgets = {
            'name': forms.TextInput(attrs={'class': 'form-control'}),
            'description': forms.Textarea(attrs={'class': 'form-control'}),
            'location': forms.TextInput(attrs={'class': 'form-control'}),
            'number_of_workers_needed': forms.NumberInput(attrs={'class': 'form-control'}),
        }
