<template>

  <div>

    <fieldset>
      <legend>Bulk Uploader</legend>
      <form>
        <p>
          If you would like to specify your list of voters by name and email address,<br/>
          you can bulk upload a list of such voters here.<br/><br/>

          Please prepare a text file of comma-separated values with the fields:
        </p>
        <pre>
           &lt;unique_id&gt;,&lt;email&gt,&lt;full name&gt;
        </pre>

        <p>
          For example:
        </p>
        <pre>
            benadida,ben@adida.net,Ben Adida
            bobsmith,bob@acme.org,Bob Smith
            ...
        </pre>

        <p>
          The easiest way to prepare such a file is to use a spreadsheet program and to export as "CSV".
        </p>
        <div style="color: red;" v-if="error">{{ error }}</div>

      </form>

      <a class="button" href="election@voters@upload election_slug=election.slug">bulk upload voters</a>

      <!--    <div id="done" style="display:none;">-->
      <!--      OK, done uploading.<br/>-->
      <!--      You can now <a href="./manage">view the list of voters</a>.-->
      <!--    </div>-->

    </fieldset>

    <fieldset v-if="voter_files">
      <legend>Prior Bulk Uploads:</legend>
      <ul>
        <li v-for="voter_file in voter_files">
          {{ voter_file.voter_file ? voter_file.voter_file.size : voter_file.voter_file_content.length }} bytes, at
          {{ voter_file.uploaded_at }}:
          <span v-if="voter_file.processing_finished_at">done processing: {{ voter_file.num_voters }} voters loaded</span>
          <div v-else>
            <span v-if="voter_file.processing_started_at">currently processing</span>
            <span v-else>not yet processed</span>
          </div>
        </li>
      </ul>
    </fieldset>

  </div>

</template>

<script>
export default {
  name: "BulkUploader",

  data() {
    return {
      error: null,
      voter_files: null // TODO
    }
  },

  // TODO

  //election@voters@upload election_slug=election.slug
}
</script>

<style scoped>
pre {
  background: #dedede;
  padding: 10px;
  border-radius: 10px;
  padding-bottom: 0px;
}
</style>
