from django.core.paginator import Paginator, EmptyPage, PageNotAnInteger
from django.shortcuts import render, get_object_or_404
from .models import Movie, TVShow, VoiceActor, MovieRole, DubbingCompany
from django.db.models import F
from django.http import Http404
from django.utils.text import slugify
from datetime import datetime
import string

def home(request):
    movies = Movie.objects.all()
    latest_movies = Movie.objects.order_by('-release_year')[:3]  # Fetch the 3 latest movies
    return render(request, 'movies/home.html', {'latest_movies': latest_movies, 'movies': movies})

def movie_detail(request, slug):
    # Split the slug into parts using hyphen as the separator.
    parts = slug.split('-')
    # We expect at least two parts: one (or more) for the title and one for the release year.
    if len(parts) < 2:
        raise Http404("Movie not found")
    
    # The last element should be the release year.
    release_year_part = parts[-1]
    # Everything before that makes up the slugified title.
    title_slug = '-'.join(parts[:-1])
    
    try:
        release_year = int(release_year_part)
    except ValueError:
        # If the conversion fails, the slug is invalid.
        raise Http404("Movie not found")
    
    # First, filter movies by the release year for efficiency.
    candidate_movies = Movie.objects.filter(release_year=release_year)
    movie = None
    for candidate in candidate_movies:
        # Compare the slugified version of the candidate title with the title portion of the URL.
        if slugify(candidate.title) == title_slug:
            movie = candidate
            break

    if not movie:
        raise Http404("Movie not found")
    
    return render(request, 'movies/detail.html', {'movie': movie})

def tv_shows(request):
    # Get filter parameters
    query = request.GET.get('q', '')
    sort_by = request.GET.get('sort', 'title')
    direction = request.GET.get('direction', 'asc')
    start_year_str = request.GET.get('start_year', '')
    end_year_str = request.GET.get('end_year', '')
    letter = request.GET.get('letter', '')

    # Initialize base queryset
    tv_shows_list = TVShow.objects.all()

    # -- Apply filters --
    # Search filter
    if query:
        tv_shows_list = tv_shows_list.filter(title__icontains=query)

    # Year range filter
    if start_year_str or end_year_str:
        try:
            start_year = int(start_year_str) if start_year_str else 1900
            end_year = int(end_year_str) if end_year_str else datetime.now().year
            
            # Swap if reversed
            if start_year > end_year:
                start_year, end_year = end_year, start_year
                
            tv_shows_list = tv_shows_list.filter(release_year__range=(start_year, end_year))
        except ValueError:
            pass

    # Starting letter filter
    if letter and letter.isalpha():
        tv_shows_list = tv_shows_list.filter(title__istartswith=letter)

    # -- Sorting --
    sort_order = f'-{sort_by}' if direction == 'desc' else sort_by
    tv_shows_list = tv_shows_list.order_by(sort_order)

    # -- Pagination --
    paginator = Paginator(tv_shows_list, 12)
    page_number = request.GET.get('page')
    try:
        tv_shows_page = paginator.page(page_number)
    except PageNotAnInteger:
        tv_shows_page = paginator.page(1)
    except EmptyPage:
        tv_shows_page = paginator.page(paginator.num_pages)

    # Prepare context
    context = {
        'tv_shows': tv_shows_page,
        'query': query,
        'sort_by': sort_by,
        'direction': direction,
        'start_year': start_year_str,
        'end_year': end_year_str,
        'letter': letter,
        'distinct_years': TVShow.objects.values_list('release_year', flat=True).distinct().order_by('-release_year'),
        'alphabet': list(string.ascii_uppercase)
    }
    
    return render(request, 'movies/tv_shows.html', context)

def movies(request):
    # Get filter parameters
    query = request.GET.get('q', '')
    sort_by = request.GET.get('sort', 'title')
    direction = request.GET.get('direction', 'asc')
    start_year_str = request.GET.get('start_year', '')
    end_year_str = request.GET.get('end_year', '')
    letter = request.GET.get('letter', '')
    company_id_str = request.GET.get('company', '')

    # Initialize base queryset
    movies_list = Movie.objects.all()  # THIS IS THE CRUCIAL INITIALIZATION

    # -- Apply filters --
    # Search filter
    if query:
        movies_list = movies_list.filter(title__icontains=query)

    # Year range filter
    if start_year_str or end_year_str:
        try:
            start_year = int(start_year_str) if start_year_str else 1900
            end_year = int(end_year_str) if end_year_str else datetime.now().year
            
            # Swap if reversed
            if start_year > end_year:
                start_year, end_year = end_year, start_year
                
            movies_list = movies_list.filter(release_year__range=(start_year, end_year))
        except ValueError:
            pass

    # Company filter
    if company_id_str:
        try:
            movies_list = movies_list.filter(dubbing_company_id=int(company_id_str))
        except ValueError:
            pass

    # Starting letter filter
    if letter and letter.isalpha():
        movies_list = movies_list.filter(title__istartswith=letter)

    # -- Sorting --
    sort_order = f'-{sort_by}' if direction == 'desc' else sort_by
    movies_list = movies_list.order_by(sort_order)

    # -- Pagination --
    paginator = Paginator(movies_list, 12)
    page_number = request.GET.get('page')
    try:
        movies_page = paginator.page(page_number)
    except PageNotAnInteger:
        movies_page = paginator.page(1)
    except EmptyPage:
        movies_page = paginator.page(paginator.num_pages)

    # Prepare context
    context = {
        'movies': movies_page,
        'query': query,
        'sort_by': sort_by,
        'direction': direction,
        'start_year': start_year_str,
        'end_year': end_year_str,
        'letter': letter,
        'company_id': company_id_str,
        'companies': DubbingCompany.objects.all(),
        'distinct_years': Movie.objects.values_list('release_year', flat=True).distinct().order_by('-release_year'),
        'alphabet': list(string.ascii_uppercase)
    }
    
    return render(request, 'movies/movies.html', context)

def tv_show_detail(request, slug):
    tv_show = get_object_or_404(TVShow, title__iexact=slug.split('-')[0], release_year=slug.split('-')[1])
    return render(request, 'movies/tv_show_detail.html', {'tv_show': tv_show})

def actors(request):
    query = request.GET.get('q')  # Get search query
    sort_by = request.GET.get('sort', 'name')  # Get sorting option, default is 'name'
    direction = request.GET.get('direction', 'asc')  # Get sorting direction, default is ascending

    # Base queryset
    actors_list = VoiceActor.objects.all()

    # Apply search filter
    if query:
        actors_list = actors_list.filter(name__icontains=query)

    # Apply sorting
    if sort_by == 'name':
        if direction == 'desc':
            actors_list = actors_list.order_by(F('name').desc())
        else:
            actors_list = actors_list.order_by(F('name').asc())
    elif sort_by == 'age':
        if direction == 'desc':
            actors_list = actors_list.order_by(F('date_of_birth').desc(nulls_last=True))
        else:
            actors_list = actors_list.order_by(F('date_of_birth').asc(nulls_last=True))

    # Paginate results
    paginator = Paginator(actors_list, 12)  # Show 10 actors per page
    page = request.GET.get('page')
    try:
        actors = paginator.page(page)
    except PageNotAnInteger:
        actors = paginator.page(1)
    except EmptyPage:
        actors = paginator.page(paginator.num_pages)

    return render(request, 'movies/actors.html', {
        'actors': actors,
        'query': query,
        'sort_by': sort_by,
        'direction': direction,
    })

def actor_detail(request, pk):
    actor = get_object_or_404(VoiceActor, pk=pk)
    movie_roles = MovieRole.objects.filter(voice_actor=actor)  # Fetch all movies where the actor has roles
    return render(request, 'movies/actor_detail.html', {'actor': actor, 'movie_roles': movie_roles})

def dubbing_company_detail(request, pk):
    company = get_object_or_404(DubbingCompany, pk=pk)
    # Use the reverse manager "voice_actors" from the related_name attribute
    voice_actors = company.voice_actors.all()
    context = {
        'company': company,
        'voice_actors': voice_actors,
    }
    return render(request, 'movies/dubbing_company_detail.html', context)