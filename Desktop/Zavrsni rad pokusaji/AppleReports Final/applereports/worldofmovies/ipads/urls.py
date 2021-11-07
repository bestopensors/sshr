from django.urls import path
from . import views

urlpatterns = [
    path('', views.index, name='ipads'),
    path('<int:ipad_id>', views.ipad, name='ipad'),
    path('pretraga', views.pretraga, name='pretraga'),
]