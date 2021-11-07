from django.urls import path
from . import views

urlpatterns = [
    path('', views.index, name='watches'),
    path('<int:watch_id>', views.watch, name='watch'),
    path('pretraga', views.pretraga, name='pretraga'),
]