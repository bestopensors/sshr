from django.shortcuts import render, get_object_or_404
from .models import Phone
from django.core.paginator import EmptyPage, PageNotAnInteger, Paginator

def index(request):
    # filmovi = Film.objects.all()
    phones = Phone.objects.order_by('-released')

    paginator = Paginator(phones, 6)
    page = request.GET.get('page')
    paged_phones = paginator.get_page(page)

    context = {
        'phones': paged_phones
    }
    return render(request, 'phones/phones.html', context)

def phone(request, phone_id):
    phone = get_object_or_404(Phone, pk=phone_id)

    context = {
        'phone': phone,
    }

    return render(request, 'phones/phone.html', context)

def pretraga(request):
    return render(request, 'phones/pretraga.html')
