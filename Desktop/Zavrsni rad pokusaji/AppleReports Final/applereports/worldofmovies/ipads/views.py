from django.shortcuts import render, get_object_or_404
from .models import Ipad
from django.core.paginator import EmptyPage, PageNotAnInteger, Paginator

def index(request):
    # filmovi = Film.objects.all()
    ipads = Ipad.objects.order_by('-released')

    paginator = Paginator(ipads, 6)
    page = request.GET.get('page')
    paged_ipads = paginator.get_page(page)

    context = {
        'ipads': paged_ipads
    }
    return render(request, 'ipads/ipads.html', context)

def ipad(request, ipad_id):
    ipad = get_object_or_404(Ipad, pk=ipad_id)

    context = {
        'ipad': ipad,
    }

    return render(request, 'ipads/ipad.html', context)

def pretraga(request):
    return render(request, 'ipads/pretraga.html')
