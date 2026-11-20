Feature:
    Ability for admins to view a contact request

    Background:
        Given account "MyCompany" with id "666" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given contact request with id "CR-1" of account "666" exists

    Scenario: As an admin I can view a contact request
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/contact-requests/CR-1"
        Then the response status should be 200
        Then I see a text "Solicitante CR-1"
        Then I see a text "CR-1@example.com"
        Then I see a text "Marcar como gestionada"

    Scenario: As an admin I don't see the actions of a managed contact request
        Given contact request "CR-1" is managed
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/contact-requests/CR-1"
        Then the response status should be 200
        Then I see a text "Gestionada"
        Then I do not see a text "Marcar como gestionada"

    Scenario: As an admin I get a not found for an unknown contact request
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/contact-requests/unknown"
        Then the response status should be 404

    Scenario: As an owner I can't view a contact request
        When I am logged as "owner@dediez.software"
        When I go to "/es/admin/contact-requests/CR-1"
        Then the response status should be 403
