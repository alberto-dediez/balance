Feature:
    I want to make sure that the software allow to register a event

    Scenario: As an owner, I can access to register new events
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/events/register"
        Then the response status should be 200
        Then I see a form

    Scenario: As an owner, I can register an event with its venue location
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given destination "Madrid" with id "abc123" exists
        Given place "venue-ref" named "IFEMA, Madrid" at "40.4680", "-3.6160" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/events/register"
        When I pick "abc123" in the autocomplete "location"
        When I pick "venue-ref" in the autocomplete "venuePlaceReference"
        When I submit a form with "name:Evento Nuevo|location:abc123|venuePlaceReference:venue-ref|size:Small|sector:business|typology:local|assemblyStartDate:2026-01-01|assemblyEndDate:2026-01-02|celebrationStartDate:2026-01-03|celebrationEndDate:2026-01-04|breakdownStartDate:2026-01-05|breakdownEndDate:2026-01-06" and button "Registrar"
        When I follow redirect
        Then the response status should be 200
        Then event named "Evento Nuevo" has venue location "IFEMA, Madrid"

    Scenario: As normal user I can't register new events
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/events/register"
        Then the response status should be 403
