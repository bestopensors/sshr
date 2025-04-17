from django.conf import settings
from django.conf.urls.static import static
from django.urls import path
from . import views

urlpatterns = [
    path('', views.home, name='home'),
    path('movies/', views.movies, name='movies'),
    path('movies/<slug:slug>/', views.movie_detail, name='movie_detail'),
    path('tv-shows/', views.tv_shows, name='tv_shows'),
    path('tv-shows/<slug:slug>/', views.tv_show_detail, name='tv_show_detail'),  # TV show detail with slug
    path('actors/', views.actors, name='actors'),
    path('actors/<int:pk>/', views.actor_detail, name='actor_detail'),
    path('companies/<int:pk>/', views.dubbing_company_detail, name='dubbing_company_detail'),
]

if settings.DEBUG:
    urlpatterns += static(settings.STATIC_URL, document_root=settings.STATIC_ROOT)

