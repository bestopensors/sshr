from django.db import models

class Tvrtka(models.Model):
    naziv = models.CharField(max_length=200)
    logo = models.ImageField(upload_to='photos/%y/%m/%d',blank=True)
    godinaOsnivanja = models.IntegerField()
    sjediste = models.CharField(max_length=100)
    prihod = models.IntegerField(default=0)

    def __str__(self):
        return self.naziv

