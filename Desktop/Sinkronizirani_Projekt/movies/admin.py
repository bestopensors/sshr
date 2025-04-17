from django.contrib import admin
from .models import Movie, VoiceActor, DubbingCompany, MovieRole, TVShow, Season, Episode, EpisodeRole
from django import forms  # Import forms module
from django.utils.html import format_html

class MovieRoleForm(forms.ModelForm):
    class Meta:
        model = MovieRole
        fields = '__all__'

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        # Sort voice_actor field alphabetically
        self.fields['voice_actor'].queryset = VoiceActor.objects.order_by('name')


class MovieRoleInline(admin.TabularInline):
    model = MovieRole
    form = MovieRoleForm  # Use the custom form
    extra = 1  # Number of empty forms to display by default
    fields = ('voice_actor', 'character_name', 'character_image')  # Include character_image
    readonly_fields = ('character_image_preview',)  # Add a preview for character images

    # Method to display a preview of the character image
    def character_image_preview(self, obj):
        if obj.character_image:
            return format_html('<a href="{}" target="_blank"><img src="{}" width="50" height="50" style="border-radius: 5px;"></a>',
                               obj.character_image.url, obj.character_image.url)
        return "No Image"

    character_image_preview.short_description = "Character Image Preview"

@admin.register(Movie)
class MovieAdmin(admin.ModelAdmin):
    list_display = ('title', 'release_year', 'dubbing_company')
    search_fields = ('title', 'original_title')
    list_filter = ('release_year', 'dubbing_company')

    fieldsets = (
        (None, {
            'fields': ('title', 'original_title', 'release_year', 'description', 'poster_image')
        }),
        ('Release Dates (Croatia)', {
            'fields': ('croatia_dvd_release_date', 'croatia_bluray_release_date'),
        }),
        ('Release Dates (US)', {
            'fields': ('us_dvd_release_date', 'us_bluray_release_date'),
        }),
        ('Dubbing Details', {
            'fields': ('dubbing_company',),
        }),
    )

    inlines = [MovieRoleInline]  # Add the inline form for MovieRole


@admin.register(VoiceActor)
class VoiceActorAdmin(admin.ModelAdmin):
    list_display = ('name', 'has_bio', 'has_photo', 'has_instagram', 'has_facebook', 'has_x',)
    search_fields = ('name',)

    fieldsets = (
        (None, {
            'fields': ('name', 'bio', 'photo')
        }),
        ('Personal Information', {
            'fields': ('place_of_birth', 'date_of_birth', 'gender', 'date_of_death'),
        }),
        ('Social Media Links', {
            'fields': ('instagram', 'facebook', 'x'),
        }),
        ('Dubbing Companies', {
            'fields': ('dubbing_companies',),
        }),
    )
    filter_horizontal = ('dubbing_companies',)  # Enables a nicer widget for ManyToMany relations

    @admin.display(boolean=True, description="Has Bio")
    def has_bio(self, obj):
        return bool(obj.bio)

    @admin.display(boolean=True, description="Has Photo")
    def has_photo(self, obj):
        return bool(obj.photo)

    @admin.display(boolean=True, description="Has Instagram")
    def has_instagram(self, obj):
        return bool(obj.instagram)

    @admin.display(boolean=True, description="Has Facebook")
    def has_facebook(self, obj):
        return bool(obj.facebook)

    @admin.display(boolean=True, description="Has X")
    def has_x(self, obj):
        return bool(obj.x)



@admin.register(DubbingCompany)
class DubbingCompanyAdmin(admin.ModelAdmin):
    list_display = ('name', 'website', 'logo_preview')
    readonly_fields = ('logo_preview',)
    search_fields = ('name',)
    fieldsets = (
        (None, {
            'fields': ('name', 'website', 'logo', 'logo_preview')
        }),
        ('Social Media Links', {
            'fields': ('instagram', 'facebook', 'x'),
        }),
    )

    def logo_preview(self, obj):
        if obj.logo:
            return format_html('<img src="{}" style="max-width:200px; max-height:200px;" />', obj.logo.url)
        return "No Logo Provided"
    logo_preview.short_description = "Logo Preview"

@admin.register(TVShow)
class TVShowAdmin(admin.ModelAdmin):
    list_display = ('title', 'release_year', 'total_seasons', 'dubbed_seasons')
    search_fields = ('title',)
    list_filter = ('release_year',)


class SeasonInline(admin.TabularInline):
    model = Season
    extra = 1


@admin.register(Season)
class SeasonAdmin(admin.ModelAdmin):
    list_display = ('tv_show', 'season_number', 'is_dubbed', 'total_episodes', 'dubbed_episodes')
    list_filter = ('is_dubbed',)


class EpisodeInline(admin.TabularInline):
    model = Episode
    extra = 1


@admin.register(Episode)
class EpisodeAdmin(admin.ModelAdmin):
    list_display = ('season', 'episode_number', 'title', 'is_dubbed')
    list_filter = ('is_dubbed',)


class EpisodeRoleInline(admin.TabularInline):
    model = EpisodeRole
    extra = 1


@admin.register(EpisodeRole)
class EpisodeRoleAdmin(admin.ModelAdmin):
    list_display = ('episode', 'voice_actor', 'character_name')
