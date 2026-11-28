Feature:
    I want to make sure that the owner of an account registers contests

    Background:
        Given account "Hoteles Sol" with id "A1" exists
        Given account "A1" has the contests feature enabled
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "A1" exists
        Given event "Congreso anual" with id "E1" of account "A1" exists

    Scenario: The owner registers a contest for the attendee survey of an event
        When I am logged as "owner@dediez.software"
        When I go to "/es/carbon-footprint/contests/register"
        Then the response status should be 200
        Then I see a text "El concurso es responsabilidad exclusiva del organizador."
        Then I see an option "E1" in form field "[eventIds]"
        When I fill the unprefixed field "contest[startsAt]" with "2026-01-01T10:00"
        When I fill the unprefixed field "contest[endsAt]" with "2099-01-31T23:00"
        When I submit a form with "eventIds:[E1]|participationMode:calculation|awardMode:draw|winnersCount:1|name:Concurso de verano|prizeDescription:Una noche de hotel|terms:Bases del concurso|organizerName:Hoteles Sol S.L.|organizerTaxId:B12345678|organizerContactEmail:legal@example.com" and button "Guardar y salir"
        Then I am redirected to route "contests"
        Then I see a text "Concurso registrado con éxito"
        Then I see a text "Concurso de verano"
        Then I see a text "Borrador"

    Scenario: The owner cannot register a contest with the end before the start
        When I am logged as "owner@dediez.software"
        When I go to "/es/carbon-footprint/contests/register"
        When I fill the unprefixed field "contest[startsAt]" with "2099-02-01T10:00"
        When I fill the unprefixed field "contest[endsAt]" with "2099-01-31T23:00"
        When I submit a form with "eventIds:[E1]|participationMode:calculation|awardMode:draw|winnersCount:1|name:Concurso|prizeDescription:Premio|terms:Bases|organizerName:Org|organizerTaxId:B1|organizerContactEmail:legal@example.com" and button "Guardar y salir"
        Then the response status should be 200
        Then I see a text "La fecha de fin debe ser posterior a la de inicio."

    Scenario: An account without the contests feature cannot register contests
        Given account "Sin concursos" with id "A3" exists
        Given user "other@dediez.software" with role "ROLE_OWNER" of account "A3" exists
        When I am logged as "other@dediez.software"
        When I go to "/es/carbon-footprint/contests/register"
        Then the response status should be 403
