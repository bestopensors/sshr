from django.shortcuts import render, get_object_or_404
from .models import Watch
from django.core.paginator import EmptyPage, PageNotAnInteger, Paginator

def index(request):
    # filmovi = Film.objects.all()
    watches = Watch.objects.order_by('-released')

    paginator = Paginator(watches, 6)
    page = request.GET.get('page')
    paged_watches = paginator.get_page(page)

    context = {
        'watches': paged_watches
    }
    return render(request, 'watches/watches.html', context)

def watch(request, watch_id):
    watch = get_object_or_404(Watch, pk=watch_id)

    context = {
        'watch': watch,
    }

    return render(request, 'watches/watch.html', context)

def pretraga(request):
    return render(request, 'watches/pretraga.html')
