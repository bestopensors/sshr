from django.urls import path

from . import views

urlpatterns = [
    #home stranica povezana na metodu index u view dokumentu
    path('', views.index, name='index'),
    path('o-nama', views.o_nama, name='o-nama'),
    path('management/', views.management, name='management'),
    path('kontakt', views.kontakt, name='kontakt'),
]