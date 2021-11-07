from django.urls import path
from . import views

urlpatterns = [
    path('', views.index, name='phones'),
    path('<int:phone_id>', views.phone, name='phone'),
    path('pretraga', views.pretraga, name='pretraga'),
]