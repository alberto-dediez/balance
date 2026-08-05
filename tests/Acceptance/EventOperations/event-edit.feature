Feature:
    I want to make sure that the system allow to edit a event

    Scenario: As an owner, I can edit my own events
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given destination "Madrid" with id "abc123" exists
        Given event "Evento A" with id "333" of account "666" with location "abc123" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/events/edit/333"
        When I submit a form with "name:Nombre Actualizado|location:abc123|size:Small|sector:business|typology:local|assemblyStartDate:2026-01-01|assemblyEndDate:2026-01-02|celebrationStartDate:2026-01-03|celebrationEndDate:2026-01-04|breakdownStartDate:2026-01-05|breakdownEndDate:2026-01-06" and button "Guardar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Evento actualizado con éxito"

    Scenario: As an owner, I can set the venue location of my event
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given destination "Madrid" with id "abc123" exists
        Given place "venue-ref" named "IFEMA, Madrid" at "40.4680", "-3.6160" exists
        Given event "Evento A" with id "333" of account "666" with location "abc123" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/events/edit/333"
        When I pick "venue-ref" in the autocomplete "venuePlaceReference"
        When I submit a form with "name:Evento A|location:abc123|venuePlaceReference:venue-ref|size:Small|sector:business|typology:local|assemblyStartDate:2026-01-01|assemblyEndDate:2026-01-02|celebrationStartDate:2026-01-03|celebrationEndDate:2026-01-04|breakdownStartDate:2026-01-05|breakdownEndDate:2026-01-06" and button "Guardar"
        When I follow redirect
        Then the response status should be 200
        Then event "333" has venue location "IFEMA, Madrid"

    Scenario: As an owner, I can't edit events from other accounts
        Given account "MyCompany" with id "666" exists
        Given account "OtherCompany" with id "777" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given event "Evento Ajeno" with id "333" of account "777" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/events/edit/333"
        Then the response status should be 403

    Scenario: As a user I can't edit events
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given event "Evento A" with id "333" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/events/edit/333"
        Then the response status should be 403
