from django.contrib import admin
from .models import Service, SubService, Profile, Rating, PaidAd

@admin.register(PaidAd)
class PaidAdAdmin(admin.ModelAdmin):
    list_display = ('name', 'service', 'description')

admin.site.register(Service)
admin.site.register(SubService)
admin.site.register(Profile)
admin.site.register(Rating)
