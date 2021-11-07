from django.contrib import admin
from phones.models import Phone
from tvrtke.models import Tvrtka
from watches.models import Watch
from ipads.models import Ipad

# Register your models here.

admin.site.register(Phone)
admin.site.register(Tvrtka)
admin.site.register(Watch)
admin.site.register(Ipad)