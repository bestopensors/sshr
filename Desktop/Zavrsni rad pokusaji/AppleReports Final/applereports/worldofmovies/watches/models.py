from django.db import models
from tvrtke.models import Tvrtka

# Create your models here.
# slike neće direktno spremati u bazu, već ćemo spremati link na njih preko kojeg ćemo ih prikazati na stranici

class Watch(models.Model):
    #tvrtka = models.ForeignKey(Tvrtka, on_delete=models.DO_NOTHING)
    name = models.CharField(max_length=50)
    plakat = models.ImageField(upload_to='photos/%y/%m/%d', blank=True)
    description = models.TextField(blank=True)
    released = models.CharField(max_length=30, blank=True)
    body = models.TextField(blank=True)
    display = models.TextField(blank=True)
    platform = models.TextField(blank=True)
    memory = models.TextField(blank=True)
    camera = models.TextField(blank=True)
    battery = models.TextField(blank=True)
    misc = models.TextField(blank=True)
    price = models.TextField(default=False)
    sound = models.TextField(default=False)
    features = models.TextField(default=False)
    latest = models.BooleanField(default=False)

    # prikazivat će glavni atribut naše klase
    def __str__(self):
        return self.name

    def dioSadrzaja(self):
        return Watch.description[:10]
