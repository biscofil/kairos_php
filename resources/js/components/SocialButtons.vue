<template>
  <div>
    <span style="margin: 20px;">
      <a name="fb_share" type="box_count" :share_url="url" :href="fb_url">Share</a>
      <script src="http://static.ak.fbcdn.net/connect.php/js/FB.Share" type="text/javascript"></script>
    </span>
    <a href="http://twitter.com/share" class="twitter-share-button" :data-url="url" :data-text="text"
       data-count="none" data-via="heliosvoting">Tweet</a>
    <script type="text/javascript" src="http://platform.twitter.com/widgets.js"></script>
  </div>
</template>

<script>
export default {
  name: "SocialButtons",

  props: {
    url: {
      required: true
    },
    text: {
      required: true
    }
  },

  computed: {
    fb_url() {
      return "http://www.facebook.com/sharer.php?t=" + this.text //TODO url encode
    }
  }
}
</script>

<style scoped>

</style>