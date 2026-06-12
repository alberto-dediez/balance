Feature:
    Ability to see the incidence dashboard in web

    Scenario: As an owner, I can access the incidence dashboard of my account
        Given account "MyAccount" with id "my-account" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "my-account" exists
        Given incidence "Avería cámara" with id "incidence-1" of account "my-account" exists
        Given incidence "Avería horno" with id "incidence-2" of account "my-account" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/incidences/dashboard"
        Then the response status should be 200
        Then I see a text "Tipo más frecuente"
        Then I see a text "Incidencias por tipo"

    Scenario: As an owner, I cannot see incidences of other accounts in the dashboard
        Given account "MyAccount" with id "my-account" exists
        Given account "OtherAccount" with id "other-account" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "my-account" exists
        Given incidence "Avería cámara" with id "incidence-1" of account "other-account" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/incidences/dashboard"
        Then the response status should be 200
        Then I see a text "No hay incidencias para los filtros seleccionados"

    Scenario: As a normal user, I can't access the incidence dashboard
        Given account "MyAccount" with id "my-account" exists
        Given user "user1@dediez.software" with role "ROLE_USER" of account "my-account" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/incidences/dashboard"
        Then the response status should be 403

    Scenario: As an admin, I can't access the incidence dashboard
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/incidences/dashboard"
        Then the response status should be 403
