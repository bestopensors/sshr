from django.shortcuts import render
from django.http import HttpResponse
from phones.models import Phone
from ipads.models import Ipad
from watches.models import Watch

def index(request):
    phones = Phone.objects.all().filter(latest=True)[:1]
    ipads = Ipad.objects.all().filter(latest=True)[:1]
    watches = Watch.objects.all().filter(latest=True)[:1]

    context = {
        'phones': phones,
        'ipads': ipads,
        'watches': watches,
    }

    return render(request, 'stranice/index.html', context)

def o_nama(request):
    return render(request, 'stranice/o-nama.html')

def kontakt(request):
    return render(request, 'stranice/kontakt.html')

def management(request):
    return render(request, 'management/management.html')