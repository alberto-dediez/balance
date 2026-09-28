Feature:
    I want to make sure that the software seeds the standard materiality masters

    Scenario: Seeding registers the standard topics and the initial questions
        Given standard materiality masters are seeded
        Then there should be 15 standard materiality topics
        Then there should be 5 materiality questions

    Scenario: The seeded topics and questions are shown in every language
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given standard materiality masters are seeded
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/materiality-topics"
        Then I see a text "Viajes de negocios"
        When I go to "/en/admin/materiality-topics"
        Then I see a text "Business travel"
        When I go to "/en/admin/materiality-questions"
        Then I see a text "How large are the estimated emissions"

    Scenario: As an admin, I can seed the standard masters from the list and seeding again registers nothing
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/materiality-topics"
        When I click on link "Sembrar maestros estándar"
        Then I am redirected to route "admin_materiality_topics"
        Then I see a text "Se han registrado 15 categorías y 5 preguntas estándar"
        Then there should be 15 standard materiality topics
        Then there should be 5 materiality questions
        When I click on link "Sembrar maestros estándar"
        Then I am redirected to route "admin_materiality_topics"
        Then I see a text "Los maestros estándar ya estaban registrados"

    Scenario: Without a valid token the seeding is refused
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/materiality/seed"
        Then the response status should be 403
        Then there should be 0 standard materiality topics

    Scenario: As an owner, I can't seed the standard masters
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/admin/materiality/seed"
        Then the response status should be 403
