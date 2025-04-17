from django.db import models
from datetime import date
from django.utils.text import slugify

class DubbingCompany(models.Model):
    name = models.CharField(max_length=100)
    website = models.URLField(blank=True)
    instagram = models.URLField(blank=True)
    facebook = models.URLField(blank=True)
    x = models.URLField(blank=True)
    logo = models.ImageField(upload_to='companies/', blank=True)

    def __str__(self):
        return self.name


class VoiceActor(models.Model):
    name = models.CharField(max_length=100)
    bio = models.TextField(blank=True)
    photo = models.ImageField(upload_to='actors/', blank=True)
    place_of_birth = models.CharField(max_length=100, blank=True)
    date_of_birth = models.DateField(null=True, blank=True)
    dubbing_companies = models.ManyToManyField(DubbingCompany, blank=True, related_name='voice_actors')
    gender = models.CharField(max_length=20, choices=[('Male', 'Male'), ('Female', 'Female')], blank=True)
    date_of_death = models.DateField(null=True, blank=True)
    instagram = models.URLField(blank=True)
    facebook = models.URLField(blank=True)
    x = models.URLField(blank=True)

    def __str__(self):
        return self.name

    @property
    def age(self):
        if self.date_of_death and self.date_of_birth:
            return self.date_of_death.year - self.date_of_birth.year - (
                (self.date_of_death.month, self.date_of_death.day) < (self.date_of_birth.month, self.date_of_birth.day)
            )
        elif self.date_of_birth:
            today = date.today()
            return today.year - self.date_of_birth.year - (
                (today.month, today.day) < (self.date_of_birth.month, self.date_of_birth.day)
            )
        return None


class Movie(models.Model):
    title = models.CharField(max_length=200)
    description = models.TextField(blank=True)
    original_title = models.CharField(max_length=100)
    release_year = models.IntegerField()

    # Croatian release dates
    croatia_dvd_release_date = models.DateField(null=True, blank=True)
    croatia_bluray_release_date = models.DateField(null=True, blank=True)

    # US release dates
    us_dvd_release_date = models.DateField(null=True, blank=True)
    us_bluray_release_date = models.DateField(null=True, blank=True)

    dubbing_company = models.ForeignKey(DubbingCompany, on_delete=models.SET_NULL, null=True)
    poster_image = models.ImageField(upload_to='posters/')

    def __str__(self):
        return self.title

    def get_slug(self):
        return f"{slugify(self.title)}-{self.release_year}"


class MovieRole(models.Model):
    movie = models.ForeignKey(Movie, on_delete=models.CASCADE, related_name='roles')
    voice_actor = models.ForeignKey(VoiceActor, on_delete=models.CASCADE)
    character_name = models.CharField(max_length=100)
    character_image = models.ImageField(upload_to='characters/', blank=True)  # New field for character image

    def __str__(self):
        return f"{self.voice_actor.name} as {self.character_name} in {self.movie.title}"

class TVShow(models.Model):
    title = models.CharField(max_length=200)
    description = models.TextField(blank=True)
    release_year = models.IntegerField()
    poster_image = models.ImageField(upload_to='tvshows/', blank=True)

    def __str__(self):
        return self.title

    def total_seasons(self):
        return self.seasons.count()

    def dubbed_seasons(self):
        return self.seasons.filter(is_dubbed=True).count()
        
    def get_slug(self):
        return f"{slugify(self.title)}-{self.release_year}"


class Season(models.Model):
    tv_show = models.ForeignKey(TVShow, on_delete=models.CASCADE, related_name='seasons')
    season_number = models.PositiveIntegerField()
    is_dubbed = models.BooleanField(default=False)

    def __str__(self):
        return f"{self.tv_show.title} - Season {self.season_number}"

    def total_episodes(self):
        return self.episodes.count()

    def dubbed_episodes(self):
        return self.episodes.filter(is_dubbed=True).count()


class Episode(models.Model):
    season = models.ForeignKey(Season, on_delete=models.CASCADE, related_name='episodes')
    episode_number = models.PositiveIntegerField()
    title = models.CharField(max_length=200)
    is_dubbed = models.BooleanField(default=False)

    def __str__(self):
        return f"{self.season.tv_show.title} - S{self.season.season_number}E{self.episode_number}"


class EpisodeRole(models.Model):
    episode = models.ForeignKey(Episode, on_delete=models.CASCADE, related_name='roles')
    voice_actor = models.ForeignKey(VoiceActor, on_delete=models.CASCADE)
    character_name = models.CharField(max_length=100)

    def __str__(self):
        return f"{self.voice_actor.name} as {self.character_name} in {self.episode}"
