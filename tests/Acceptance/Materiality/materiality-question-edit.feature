Feature:
    I want to make sure that the software allows to edit a materiality question

    Scenario: As an admin, I can edit a materiality question
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given materiality question "¿Pregunta?" with id "q1" for criterion "magnitude" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/materiality-questions/edit/q1"
        When I submit a form with "criterion:influence|topicId:|text:¿Pregunta cambiada?|answerScale:level|weight:3|position:2" and button "Guardar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Pregunta de materialidad actualizada con éxito"
        Then I see a text "¿Pregunta cambiada?"

    Scenario: As an admin, editing in english creates a translation and keeps the spanish text
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given materiality question "¿Pregunta?" with id "q1" for criterion "magnitude" exists
        When I am logged as "admin@dediez.software"
        When I go to "/en/admin/materiality-questions/edit/q1"
        When I submit a form with "criterion:magnitude|topicId:|text:Question?|answerScale:level|weight:1|position:0" and button "Save and exit"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Question?"
        When I go to "/es/admin/materiality-questions"
        Then I see a text "¿Pregunta?"
        Then I do not see a text "Question?"

    Scenario: As an admin, editing a question of an inactive topic keeps its topic
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given materiality topic "Lavandería externa" with id "111" exists
        Given materiality topic "111" is inactive
        Given materiality question "¿Pregunta específica?" with id "q1" for criterion "risk" and topic "111" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/materiality-questions/edit/q1"
        When I submit a form with "text:¿Pregunta específica cambiada?" and button "Guardar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Pregunta de materialidad actualizada con éxito"
        Then materiality question "q1" should have topic "111"

    Scenario: As an owner, I can't edit materiality questions
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given materiality question "¿Pregunta?" with id "q1" for criterion "magnitude" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/admin/materiality-questions/edit/q1"
        Then the response status should be 403
