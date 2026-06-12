Feature:
    Ability to see the critical point dashboard in web

    Scenario: As an owner, I can access the critical point dashboard of my account
        Given account "MyAccount" with id "my-account" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "my-account" exists
        Given property "My Property" with id "myprop" of account "my-account" exists
        Given critical point "Cocina" with id "cp-1" of account "my-account" and property "myprop" exists
        Given critical point "Buffet" with id "cp-2" of account "my-account" and property "myprop" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/critical-points/dashboard"
        Then the response status should be 200
        Then I see a text "Con prevención posible"
        Then I see a text "Puntos críticos por etapa de la cadena"

    Scenario: As an owner, I cannot see critical points of other accounts in the dashboard
        Given account "MyAccount" with id "my-account" exists
        Given account "OtherAccount" with id "other-account" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "my-account" exists
        Given property "Other Property" with id "otherprop" of account "other-account" exists
        Given critical point "Cocina" with id "cp-1" of account "other-account" and property "otherprop" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/critical-points/dashboard"
        Then the response status should be 200
        Then I see a text "No hay puntos críticos para los filtros seleccionados"

    Scenario: As a normal user, I can't access the critical point dashboard
        Given account "MyAccount" with id "my-account" exists
        Given user "user1@dediez.software" with role "ROLE_USER" of account "my-account" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/critical-points/dashboard"
        Then the response status should be 403

    Scenario: As an admin, I can't access the critical point dashboard
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/critical-points/dashboard"
        Then the response status should be 403
